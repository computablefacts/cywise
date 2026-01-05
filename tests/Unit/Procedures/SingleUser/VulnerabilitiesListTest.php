<?php

uses(\Sajya\Server\Testing\ProceduralRequests::class);
use App\Models\Alert;
use App\Models\Asset;
use App\Models\HiddenAlert;
use App\Models\Port;
use App\Models\Scan;

it('lists vulnerabilities for monitored assets', function () {
    asTenant1User();
    Alert::factory(3)->for(
        Port::factory()->for(
            Scan::factory()->for(
                Asset::factory()->monitored()->create()
            )->vulnsScanEnded()->create()
        )->create()
    )->assetMonitored()->levelHigh()->create();
    Alert::factory(2)->for(
        Port::factory()->for(
            Scan::factory()->for(
                Asset::factory()->monitored()->create()
            )->vulnsScanEnded()->create()
        )->create()
    )->assetMonitored()->levelMedium()->create();
    Alert::factory(1)->for(
        Port::factory()->for(
            Scan::factory()->for(
                Asset::factory()->monitored()->create()
            )->vulnsScanEnded()->create()
        )->create()
    )->assetMonitored()->levelLow()->create();

    $response = $this
        ->setRpcRoute('v2.private.rpc.endpoint')
        ->callProcedure('vulnerabilities@list')
        ->assertExactJsonStructure([
            'id',
            'jsonrpc',
            'result' => [
                'high',
                'medium',
                'low',
            ],
        ]);

    expect($response->json('result.high'))->toBeArray()->toHaveCount(3);
    expect($response->json('result.medium'))->toBeArray()->toHaveCount(2);
    expect($response->json('result.low'))->toBeArray()->toHaveCount(1);
});

it('lists vulnerabilities for unmonitored assets', function () {
    asTenant1User();
    Alert::factory(5)->for(
        Port::factory()->for(
            Scan::factory()->for(
                Asset::factory()->unmonitored()->create()
            )->vulnsScanEnded()->create()
        )->create()
    )->assetMonitored()->levelHigh()->create();
    Alert::factory(1)->for(
        Port::factory()->for(
            Scan::factory()->for(
                Asset::factory()->unmonitored()->create()
            )->vulnsScanEnded()->create()
        )->create()
    )->assetMonitored()->levelMedium()->create();
    Alert::factory(3)->for(
        Port::factory()->for(
            Scan::factory()->for(
                Asset::factory()->unmonitored()->create()
            )->vulnsScanEnded()->create()
        )->create()
    )->assetMonitored()->levelLow()->create();

    $response = $this
        ->setRpcRoute('v2.private.rpc.endpoint')
        ->callProcedure('vulnerabilities@list');

    expect($response->json('result.high'))->toBeArray()->toHaveCount(5);
    expect($response->json('result.medium'))->toBeArray()->toHaveCount(1);
    expect($response->json('result.low'))->toBeArray()->toHaveCount(3);
});

it('lists vulnerabilities only for one level', function (string $level, int $highCount, int $mediumCount, int $lowCount) {
    asTenant1User();
    Alert::factory(3)->for(
        Port::factory()->for(
            Scan::factory()->for(
                Asset::factory()->monitored()->create()
            )->vulnsScanEnded()->create()
        )->create()
    )->assetMonitored()->levelHigh()->create();
    Alert::factory(2)->for(
        Port::factory()->for(
            Scan::factory()->for(
                Asset::factory()->monitored()->create()
            )->vulnsScanEnded()->create()
        )->create()
    )->assetMonitored()->levelMedium()->create();
    Alert::factory(1)->for(
        Port::factory()->for(
            Scan::factory()->for(
                Asset::factory()->monitored()->create()
            )->vulnsScanEnded()->create()
        )->create()
    )->assetMonitored()->levelLow()->create();

    $response = $this
        ->setRpcRoute('v2.private.rpc.endpoint')
        ->callProcedure('vulnerabilities@list', ['level' => $level]);

    expect($response->json('result.high'))->toBeArray()->toHaveCount($highCount);
    expect($response->json('result.medium'))->toBeArray()->toHaveCount($mediumCount);
    expect($response->json('result.low'))->toBeArray()->toHaveCount($lowCount);
})->with([
    'High' => ['high', 3, 0, 0],
    'Medium' => ['medium', 0, 2, 0],
    'Low' => ['low', 0, 0, 1],
]);

it('lists vulnerabilities for one particular asset', function () {
    asTenant1User();
    Alert::factory(3)->for(
        Port::factory()->for(
            Scan::factory()->for(
                Asset::factory()->monitored()->create()
            )->vulnsScanEnded()->create()
        )->create()
    )->assetMonitored()->levelHigh()->create();
    Alert::factory(2)->for(
        Port::factory()->for(
            Scan::factory()->for(
                Asset::factory()->monitored()->create()
            )->vulnsScanEnded()->create()
        )->create()
    )->assetMonitored()->levelMedium()->create();
    Alert::factory(1)->for(
        Port::factory()->for(
            Scan::factory()->for(
                $asset = Asset::factory()->monitored()->create()
            )->vulnsScanEnded()->create()
        )->create()
    )->assetMonitored()->levelLow()->create();

    $response = $this
        ->setRpcRoute('v2.private.rpc.endpoint')
        ->callProcedure('vulnerabilities@list', ['asset_id' => $asset->id])
        ->assertExactJsonStructure([
            'id',
            'jsonrpc',
            'result' => [
                'high',
                'medium',
                'low',
            ],
        ]);

    expect($response->json('result.high'))->toBeArray()->toHaveCount(0);
    expect($response->json('result.medium'))->toBeArray()->toHaveCount(0);
    expect($response->json('result.low'))->toBeArray()->toHaveCount(1);
});

test('critical vulnerabilities are grouped with high vulnerabilities', function () {
    asTenant1User();
    Alert::factory(3)->for(
        Port::factory()->for(
            Scan::factory()->for(
                Asset::factory()->monitored()->create()
            )->vulnsScanEnded()->create()
        )->create()
    )->assetMonitored()->levelCritical()->create();
    Alert::factory(2)->for(
        Port::factory()->for(
            Scan::factory()->for(
                Asset::factory()->monitored()->create()
            )->vulnsScanEnded()->create()
        )->create()
    )->assetMonitored()->levelHigh()->create();

    $response = $this
        ->setRpcRoute('v2.private.rpc.endpoint')
        ->callProcedure('vulnerabilities@list')
        ->assertExactJsonStructure([
            'id',
            'jsonrpc',
            'result' => [
                'high',
                'medium',
                'low',
            ],
        ]);

    expect($response->json('result.high'))->toBeArray()->toHaveCount(5);
    expect($response->json('result.medium'))->toBeArray()->toHaveCount(0);
    expect($response->json('result.low'))->toBeArray()->toHaveCount(0);
});

it('hides vulnerabilities by uid', function () {
    asTenant1User();
    $alerts = Alert::factory(3)->for(
        Port::factory()->for(
            Scan::factory()->for(
                Asset::factory()->monitored()->create()
            )->vulnsScanEnded()->create()
        )->create()
    )->assetMonitored()->levelMedium()->create();

    HiddenAlert::factory()->hideUid($alerts->first()->uid)->create();

    $response = $this
        ->setRpcRoute('v2.private.rpc.endpoint')
        ->callProcedure('vulnerabilities@list')
        ->assertExactJsonStructure([
            'id',
            'jsonrpc',
            'result' => [
                'high',
                'medium',
                'low',
            ],
        ]);

    expect($response->json('result.high'))->toBeArray()->toHaveCount(0);
    expect($response->json('result.medium'))->toBeArray()->toHaveCount(2);
    expect($response->json('result.low'))->toBeArray()->toHaveCount(0);
});

it('hides vulnerabilities by type', function () {
    asTenant1User();
    Alert::factory(3, ['type' => 'some_type'])->for(
        Port::factory()->for(
            Scan::factory()->for(
                Asset::factory()->monitored()->create()
            )->vulnsScanEnded()->create()
        )->create()
    )->assetMonitored()->levelMedium()->create();
    Alert::factory(2, ['type' => 'other_type'])->for(
        Port::factory()->for(
            Scan::factory()->for(
                Asset::factory()->monitored()->create()
            )->vulnsScanEnded()->create()
        )->create()
    )->assetMonitored()->levelMedium()->create();

    HiddenAlert::factory()->hideType('some_type')->create();

    $response = $this
        ->setRpcRoute('v2.private.rpc.endpoint')
        ->callProcedure('vulnerabilities@list')
        ->assertExactJsonStructure([
            'id',
            'jsonrpc',
            'result' => [
                'high',
                'medium',
                'low',
            ],
        ]);

    expect($response->json('result.high'))->toBeArray()->toHaveCount(0);
    expect($response->json('result.medium'))->toBeArray()->toHaveCount(2);
    expect($response->json('result.low'))->toBeArray()->toHaveCount(0);
});

it('hides vulnerabilities by title', function () {
    asTenant1User();
    Alert::factory(3, ['title' => 'some_title'])->for(
        Port::factory()->for(
            Scan::factory()->for(
                Asset::factory()->monitored()->create()
            )->vulnsScanEnded()->create()
        )->create()
    )->assetMonitored()->levelMedium()->create();
    Alert::factory(2, ['title' => 'other_title'])->for(
        Port::factory()->for(
            Scan::factory()->for(
                Asset::factory()->monitored()->create()
            )->vulnsScanEnded()->create()
        )->create()
    )->assetMonitored()->levelMedium()->create();

    HiddenAlert::factory()->hideTitle('some_title')->create();

    $response = $this
        ->setRpcRoute('v2.private.rpc.endpoint')
        ->callProcedure('vulnerabilities@list')
        ->assertExactJsonStructure([
            'id',
            'jsonrpc',
            'result' => [
                'high',
                'medium',
                'low',
            ],
        ]);

    expect($response->json('result.high'))->toBeArray()->toHaveCount(0);
    expect($response->json('result.medium'))->toBeArray()->toHaveCount(2);
    expect($response->json('result.low'))->toBeArray()->toHaveCount(0);
});
