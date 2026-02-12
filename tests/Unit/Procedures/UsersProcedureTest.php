<?php

namespace Tests\Unit\Procedures;

use App\Events\SendAuditReport;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Spatie\Permission\Models\Permission;
use Tests\TestCaseWithDb;

class UsersProcedureTest extends TestCaseWithDb
{
    protected function setUp(): void
    {
        parent::setUp();
        Permission::findOrCreate('call.users.togglegetsauditreport', 'web');
        Permission::findOrCreate('call.users.sendauditreport', 'web');
    }

    public function test_toggle_gets_audit_report_self(): void
    {
        $user = User::factory()->create(['gets_audit_report' => false]);
        $this->actingAs($user);
        $user->givePermissionTo('call.users.togglegetsauditreport');
        $response = $this->postJson('/api/v2/private/endpoint', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'users@toggleGetsAuditReport',
            'params' => []
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('result.msg', "The user {$user->name} will get a weekly audit report.");

        $this->assertTrue((bool)$user->fresh()->gets_audit_report);
    }

    public function test_toggle_gets_audit_report_explicit_false(): void
    {
        $user = User::factory()->create(['gets_audit_report' => true]);
        $this->actingAs($user);
        $user->givePermissionTo('call.users.togglegetsauditreport');
        $response = $this->postJson('/api/v2/private/endpoint', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'users@toggleGetsAuditReport',
            'params' => [
                'gets_audit_report' => false
            ]
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('result.msg', "The user {$user->name} will get no audit report.");

        $this->assertFalse((bool)$user->fresh()->gets_audit_report);
    }

    public function test_toggle_gets_audit_report_by_id(): void
    {
        $admin = User::factory()->create();
        $targetUser = User::factory()->create(['gets_audit_report' => false, 'tenant_id' => $admin->tenant_id]);
        $this->actingAs($admin);
        $admin->givePermissionTo('call.users.togglegetsauditreport');
        $response = $this->postJson('/api/v2/private/endpoint', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'users@toggleGetsAuditReport',
            'params' => [
                'user_id' => $targetUser->id,
                'gets_audit_report' => true
            ]
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('result.msg', "The user {$targetUser->name} will get a weekly audit report.");

        $this->assertTrue((bool)$targetUser->fresh()->gets_audit_report);
    }

    public function test_toggle_gets_audit_report_by_email(): void
    {
        $admin = User::factory()->create();
        $targetUser = User::factory()->create(['gets_audit_report' => true, 'tenant_id' => $admin->tenant_id]);
        $this->actingAs($admin);
        $admin->givePermissionTo('call.users.togglegetsauditreport');
        $response = $this->postJson('/api/v2/private/endpoint', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'users@toggleGetsAuditReport',
            'params' => [
                'email' => $targetUser->email,
                'gets_audit_report' => false
            ]
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('result.msg', "The user {$targetUser->name} will get no audit report.");

        $this->assertFalse((bool)$targetUser->fresh()->gets_audit_report);
    }

    public function test_toggle_gets_audit_report_tenant_isolation(): void
    {
        $tenant1 = Tenant::create(['name' => 'Tenant 1']);
        $tenant2 = Tenant::create(['name' => 'Tenant 2']);
        $user1 = User::factory()->create(['tenant_id' => $tenant1->id]);
        $user2 = User::factory()->create(['tenant_id' => $tenant2->id]);
        $this->actingAs($user1);
        $user1->givePermissionTo('call.users.togglegetsauditreport');
        $response = $this->postJson('/api/v2/private/endpoint', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'users@toggleGetsAuditReport',
            'params' => [
                'user_id' => $user2->id
            ]
        ]);

        $response->assertJsonStructure(['error']);
    }

    public function test_send_audit_report_self(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $user->givePermissionTo('call.users.sendauditreport');

        Event::fake();

        $response = $this->postJson('/api/v2/private/endpoint', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'users@sendAuditReport',
            'params' => []
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('result.msg', "The email report has been sent to the user {$user->name}.");

        Event::assertDispatched(SendAuditReport::class, function ($event) use ($user) {
            return $event->user->id === $user->id;
        });
    }

    public function test_send_audit_report_by_id(): void
    {
        $admin = User::factory()->create();
        $targetUser = User::factory()->create(['tenant_id' => $admin->tenant_id]);
        $this->actingAs($admin);
        $admin->givePermissionTo('call.users.sendauditreport');

        Event::fake();

        $response = $this->postJson('/api/v2/private/endpoint', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'users@sendAuditReport',
            'params' => [
                'user_id' => $targetUser->id
            ]
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('result.msg', "The email report has been sent to the user {$targetUser->name}.");

        Event::assertDispatched(SendAuditReport::class, function ($event) use ($targetUser) {
            return $event->user->id === $targetUser->id;
        });
    }

    public function test_send_audit_report_by_email(): void
    {
        $admin = User::factory()->create();
        $targetUser = User::factory()->create(['tenant_id' => $admin->tenant_id]);
        $this->actingAs($admin);
        $admin->givePermissionTo('call.users.sendauditreport');

        Event::fake();

        $response = $this->postJson('/api/v2/private/endpoint', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'users@sendAuditReport',
            'params' => [
                'email' => $targetUser->email
            ]
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('result.msg', "The email report has been sent to the user {$targetUser->name}.");

        Event::assertDispatched(SendAuditReport::class, function ($event) use ($targetUser) {
            return $event->user->id === $targetUser->id;
        });
    }
}
