<?php

namespace Tests\Unit\WithDb;

use App\Events\CreateAsset;
use App\Helpers\VulnerabilityScannerApiUtilsFacade as ApiUtils;
use App\Jobs\TriggerDiscoveryShallow;
use App\Models\Asset;
use App\Models\Tenant;
use App\Models\User;
use Tests\TestCaseWithDbAndSeeders;

class DiscoveryShallowTest extends TestCaseWithDbAndSeeders
{
    protected function setUp(): void
    {
        $this->markTestSkipped('Disable tests with DB.');
    }
    protected function tearDown(): void
    {
        // $this->markTestSkipped('Disable tests with DB.');
    }

    public function testItCreatesAnAssetAfterDiscovery()
    {
        ApiUtils::shouldReceive('discover_public')
            ->once()
            ->with('example.com')
            ->andReturn([
                'subdomains' => ['www1.example.com', 'www1.example.com' /* duplicate! */, 'www2.example.com'],
            ]);

        /** @var User $user */
        $user = User::updateOrCreate(['name' => 'qa'], [
            'name' => 'qa',
            'email' => 'qa+test@computablefacts.com',
            'password' => 'qa4ever',
        ]);

        /** @var Tenant $tenant */
        $tenant = Tenant::updateOrCreate(['name' => 'qa'], ['name' => 'qa']);
        $user->tenant_id = $tenant->id;
        $user->save();

        CreateAsset::dispatch($user, 'example.com', false);
        CreateAsset::dispatch($user, 'example.com', false);

        $job = (new TriggerDiscoveryShallow())->withFakeQueueInteractions();
        $job->handle();

        $assetsOriginal = Asset::where('asset', 'example.com')->get();
        $assetsDiscovered = Asset::whereLike('asset', 'www%.example.com')->get();

        // Ensure no duplicate in DB
        $this->assertEquals(1, $assetsOriginal->count());
        $this->assertEquals(2, $assetsDiscovered->count());

        $this->assertEquals(1, $assetsDiscovered->filter(fn(Asset $asset) => $asset->asset === 'www1.example.com' && $asset->created_by = $this->user->id)->count());
        $this->assertEquals(1, $assetsDiscovered->filter(fn(Asset $asset) => $asset->asset === 'www2.example.com' && $asset->created_by = $this->user->id)->count());
    }
}
