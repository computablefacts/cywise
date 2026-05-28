<?php

namespace Tests\Unit\Procedures;

use App\Models\Asset;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Tests\TestCaseWithDb;

class AssetsProcedureTest extends TestCaseWithDb
{
    protected function setUp(): void
    {
        parent::setUp();
        Permission::findOrCreate('call.assets.toggleautomonitornewsubdomains', 'web');
    }

    public function test_toggle_auto_monitor_new_subdomains(): void
    {
        $user = User::factory()->create();
        $asset = Asset::factory()->create([
            'created_by' => $user->id,
            'auto_monitor_new_subdomains' => true,
            'asset' => 'example.com'
        ]);

        $this->actingAs($user);
        $user->givePermissionTo('call.assets.toggleautomonitornewsubdomains');

        $response = $this->postJson('/api/v2/private/endpoint', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'assets@toggleAutoMonitorNewSubdomains',
            'params' => [
                'asset_id' => $asset->id
            ]
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('result.msg', "New subdomains discovered for asset example.com will not be automatically monitored.");

        $this->assertFalse((bool)$asset->fresh()->auto_monitor_new_subdomains);

        $response = $this->postJson('/api/v2/private/endpoint', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'assets@toggleAutoMonitorNewSubdomains',
            'params' => [
                'asset_id' => $asset->id,
                'auto_monitor' => true
            ]
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('result.msg', "New subdomains discovered for asset example.com will be automatically monitored.");

        $this->assertTrue((bool)$asset->fresh()->auto_monitor_new_subdomains);
    }
}
