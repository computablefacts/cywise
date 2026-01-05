<?php

namespace Database\Seeders;

use App\Listeners\CreateAssetListener;
use App\Models\Scan;
use App\Models\Port;
use App\Models\Alert;
use App\Models\Asset;
use App\Models\AssetTag;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class VulnsMultiTenantsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->createVulnsTenant1();
        $this->createVulnsTenant2();
    }

    private function createVulnsTenant1(): void
    {
        $this->asTenant1User();

        // Asset 'tag1' with vulns
        $asset = Asset::factory()->monitored()->create();
        AssetTag::factory(['tag' => 'tag1', 'asset_id' => $asset->id])->create();
        Alert::factory()->for(
            Port::factory()->for(
                Scan::factory()->for($asset)->vulnsScanEnded()->create()
            )->create()
        )->levelHigh()->create();
        Alert::factory(2)->for(
            Port::factory()->for(
                Scan::factory()->for($asset)->vulnsScanEnded()->create()
            )->create()
        )->levelMedium()->create();

        // Asset 'tag2' with vulns
        $asset = Asset::factory()->monitored()->create();
        AssetTag::factory(['tag' => 'tag2', 'asset_id' => $asset->id])->create();
        Alert::factory(2)->for(
            Port::factory()->for(
                Scan::factory()->for($asset)->vulnsScanEnded()->create()
            )->create()
        )->levelHigh()->create();
        Alert::factory(3)->for(
            Port::factory()->for(
                Scan::factory()->for($asset)->vulnsScanEnded()->create()
            )->create()
        )->levelLow()->create();

        // Asset with no tag but with vulns
        $asset = Asset::factory()->monitored()->create();
        Alert::factory()->for(
            Port::factory()->for(
                Scan::factory()->for($asset)->vulnsScanEnded()->create()
            )->create()
        )->levelHigh()->create();
        Alert::factory(2)->for(
            Port::factory()->for(
                Scan::factory()->for($asset)->vulnsScanEnded()->create()
            )->create()
        )->levelMedium()->create();
        Alert::factory(4)->for(
            Port::factory()->for(
                Scan::factory()->for($asset)->vulnsScanEnded()->create()
            )->create()
        )->levelLow()->create();

    }

    private function createVulnsTenant2(): void
    {
        $this->asTenant2User();

        $asset = Asset::factory()->monitored()->create();
        AssetTag::factory(['tag' => 'tag5', 'asset_id' => $asset->id])->create();
        Alert::factory()->for(
            Port::factory()->for(
                Scan::factory()->for($asset)->vulnsScanEnded()->create()
            )->create()
        )->levelHigh()->create();
        
        // Asset with no tag but with vulns
        $asset = Asset::factory()->monitored()->create();
        Alert::factory()->for(
            Port::factory()->for(
                Scan::factory()->for($asset)->vulnsScanEnded()->create()
            )->create()
        )->levelHigh()->create();
        
    }

    private function asTenant1User()
    {
        return Auth::login($this->tenant1User());
    }

    private function asTenant2User()
    {
        return Auth::login($this->tenant2User());
    }

    private function tenant1User(): User
    {
        Role::createRoles();

        return User::firstOrCreate(
            ['email' => 'user@tenant1.com'],
            [
                'name' => 'User Tenant 1',
                'email' => 'user@tenant1.com',
                'password' => Hash::make('passwordTenant1'),
                'verified' => true,
            ]
        );
    }

    private function tenant2User(): User
    {
        Role::createRoles();

        return User::firstOrCreate(
            ['email' => 'user@tenant2.com'],
            [
                'name' => 'User Tenant 2',
                'email' => 'user@tenant2.com',
                'password' => Hash::make('passwordTenant2'),
                'verified' => true,
            ]
        );
    }
}
