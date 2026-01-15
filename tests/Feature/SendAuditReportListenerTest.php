<?php

use App\Events\SendAuditReport;
use App\Helpers\ApiUtilsFacade;
use App\Listeners\SendAuditReportListener;
use App\Mail\SimpleEmail;
use App\Models\Alert;
use App\Models\Asset;
use App\Models\AssetTag;
use App\Models\AssetTagHash;
use App\Models\Port;
use App\Models\Scan;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

test('listener ignores invalid event type', function () {

    Log::shouldReceive('error')
        ->once()
        ->with(Mockery::on(function ($arg) {
            return $arg instanceof \Exception
                && $arg->getMessage() === 'Invalid event type!';
        }));

    $listener = new SendAuditReportListener;
    $listener->handle(new \stdClass);

});

test('listener skips when user does not want audit report', function () {
    Mail::fake();

    $user = User::factory()->create(['gets_audit_report' => false]);
    $event = new SendAuditReport($user, false);

    $listener = new SendAuditReportListener;
    $listener->handle($event);

    Mail::assertNotSent(SimpleEmail::class);
});

test('listener skips when no assets exist', function () {
    Mail::fake();

    $user = User::factory()->create(['gets_audit_report' => true]);
    $event = new SendAuditReport($user, false);

    $listener = new SendAuditReportListener;
    $listener->handle($event);

    Mail::assertNotSent(SimpleEmail::class);
});

test('listener sends email when conditions are met', function () {
    Mail::fake();

    asTenant1User();
    $user = tenant1User();
    $user->update(['gets_audit_report' => true]);
    Asset::factory()->create();

    $event = new SendAuditReport($user, false);
    $listener = new SendAuditReportListener;
    $listener->handle($event);

    Mail::assertSent(SimpleEmail::class, function ($mail) {
        // dump($mail->__get('emailSubject'));
        return str_contains($mail->__get('emailSubject'), 'nouveaux actifs ont été ajoutés !');
    });
});

test('email includes onboarding cta when isOnboarding is true', function () {
    Mail::fake();

    asTenant1User();
    $user = tenant1User();
    $user->update(['gets_audit_report' => true]);
    Asset::factory()->create();

    $event = new SendAuditReport($user, true);
    $listener = new SendAuditReportListener;
    $listener->handle($event);

    Mail::assertSent(SimpleEmail::class, function ($mail) {
        // dump($mail->__get('htmlBody'));
        return str_contains($mail->__get('htmlBody'), 'finalisez votre inscription à Cywise');
    });
});

test('email subject reflects vulnerabilities severity', function ($alertLevel, $expectedMailTitle) {
    Mail::fake();

    ApiUtilsFacade::shouldReceive('translate')
        ->andReturn(['error' => 'No translation available']);

    asTenant1User();
    $user = tenant1User();
    $user->update(['gets_audit_report' => true]);
    Alert::factory(['level' => $alertLevel])->for(
        Port::factory()->for(
            Scan::factory()->for(
                Asset::factory()->monitored()->create()
            )->vulnsScanEnded()->create()
        )->create()
    )->create();

    $event = new SendAuditReport($user, false);
    $listener = new SendAuditReportListener;
    $listener->handle($event);

    Mail::assertSent(SimpleEmail::class, function ($mail) use ($expectedMailTitle) {
        // dump($mail->__get('emailSubject'));
        return str_contains($mail->__get('emailSubject'), $expectedMailTitle);
    });

})->with([
    'Critical' => ['Critical', 'vulnérabilités doivent être corrigées'],
    'High' => ['High', 'vulnérabilités doivent être corrigées'],
    'Medium' => ['Medium', 'vulnérabilités devraient être corrigées'],
    'Low' => ['Low', 'vulnérabilités ne posent pas un risque de sécurité immédiat'],
]);

test('email body contains summary section', function () {
    Mail::fake();

    asTenant1User();
    $user = tenant1User();
    $user->update(['gets_audit_report' => true]);
    Asset::factory()->create();

    $event = new SendAuditReport($user, false);
    $listener = new SendAuditReportListener;
    $listener->handle($event);

    Mail::assertSent(SimpleEmail::class, function ($mail) {
        // dump($mail->__get('htmlBody'));
        return str_contains($mail->__get('htmlBody'), 'résumé des résultats');
    });
});

test('email contains correct vulnerability counts and severity messages', function () {
    Mail::fake();

    ApiUtilsFacade::shouldReceive('translate')
        ->andReturn(['error' => 'No translation available']);

    asTenant1User();
    $user = tenant1User();
    $user->update(['gets_audit_report' => true]);
    // 2 high alerts
    Alert::factory(2)->for(
        Port::factory()->for(
            Scan::factory()->for(
                Asset::factory()->monitored()->create()
            )->vulnsScanEnded()->create()
        )->create()
    )->levelHigh()->create();
    // 3 medium alerts
    Alert::factory(3)->for(
        Port::factory()->for(
            Scan::factory()->for(
                Asset::factory()->monitored()->create()
            )->vulnsScanEnded()->create()
        )->create()
    )->levelMedium()->create();
    // 5 low alerts
    Alert::factory(5)->for(
        Port::factory()->for(
            Scan::factory()->for(
                Asset::factory()->monitored()->create()
            )->vulnsScanEnded()->create()
        )->create()
    )->levelLow()->create();

    $event = new SendAuditReport($user, false);
    $listener = new SendAuditReportListener;
    $listener->handle($event);

    Mail::assertSent(SimpleEmail::class, function ($mail) {
        // dump($mail->__get('htmlBody'));
        $htmlBody = $mail->__get('htmlBody');

        return str_contains($htmlBody, '<b>2</b> vulnérabilités critiques <b>doivent</b> être corrigées.')
            && str_contains($htmlBody, '<b>3</b> vulnérabilités de criticité moyenne <b>devraient</b> être corrigées.')
            && str_contains($htmlBody, '<b>5</b> vulnérabilités de criticité basse ne posent pas un risque de sécurité immédiat.');
    });

});

test('email contains vulnerabilities from a shared asset', function () {
    Mail::fake();

    ApiUtilsFacade::shouldReceive('translate')
        ->andReturn(['error' => 'No translation available']);

    asTenant1User();
    // 1 high alert for an asset with 'tag1'
    $asset = Asset::factory()->monitored()->create();
    AssetTag::factory(['tag' => 'tag1', 'asset_id' => $asset->id])->create();
    Alert::factory()->for(
        Port::factory()->for(
            Scan::factory()->for($asset)->vulnsScanEnded()->create()
        )->create()
    )->levelHigh()->create();
    // 2 high alerts for an asset with 'tag2'
    $asset = Asset::factory()->monitored()->create();
    AssetTag::factory(['tag' => 'tag2', 'asset_id' => $asset->id])->create();
    Alert::factory(2)->for(
        Port::factory()->for(
            Scan::factory()->for($asset)->vulnsScanEnded()->create()
        )->create()
    )->levelHigh()->create();
    // Share asset with tag1 to tenant2User
    AssetTagHash::factory([
        'hash' => 'user2@tenant1.com',
        'tag' => 'tag1',
    ])->create();

    // Send email to tenant1User2
    $user = User::factory([
        'email' => 'user2@tenant1.com',
        'tenant_id' => tenant1User()->tenant_id,
    ])->create();
    $user->update(['gets_audit_report' => true]);

    $event = new SendAuditReport($user, false);
    $listener = new SendAuditReportListener;
    $listener->handle($event);

    Mail::assertSent(SimpleEmail::class, function ($mail) {
        dump($mail->__get('htmlBody'));
        $htmlBody = $mail->__get('htmlBody');

        return str_contains($htmlBody, '<b>2</b> vulnérabilités critiques <b>doivent</b> être corrigées.')
            && str_contains($htmlBody, '<b>3</b> vulnérabilités de criticité moyenne <b>devraient</b> être corrigées.')
            && str_contains($htmlBody, '<b>5</b> vulnérabilités de criticité basse ne posent pas un risque de sécurité immédiat.');
    });

})->todo('Must be completed');
