<?php

namespace Tests\Feature;

use App\Helpers\VulnerabilityScannerApiUtilsFacade as ApiUtils;
use App\Models\Trial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCaseWithDb;

class DiscoveryTest extends TestCaseWithDb
{
    use RefreshDatabase;

    public function test_discovery_calls_api_when_no_cache()
    {
        $hash = Str::random(128);
        Trial::create([
            'hash' => $hash,
            'domain' => 'example.com',
        ]);

        ApiUtils::shouldReceive('discover_public')
            ->once()
            ->with('example.com')
            ->andReturn(['subdomains' => ['www.example.com']]);

        $response = $this->post(route('tools.discovery'), ['hash' => $hash]);
        $response->assertStatus(200);
        $this->assertEquals(['www.example.com'], $response->json());
        $this->assertTrue(Cache::has("cybercheck:discovery:{$hash}"));
        $this->assertEquals(['www.example.com'], Cache::get("discovery_{$hash}"));
    }

    public function test_discovery_uses_cache_when_valid()
    {
        $hash = Str::random(128);
        Trial::create([
            'hash' => $hash,
            'domain' => 'example.com',
        ]);

        Cache::put("cybercheck:discovery:{$hash}", ['cached.example.com'], now()->addDay());

        ApiUtils::shouldReceive('discover_public')->never();

        $response = $this->post(route('tools.discovery'), ['hash' => $hash]);
        $response->assertStatus(200);
        $this->assertEquals(['cached.example.com'], $response->json());
    }

    public function test_discovery_does_not_overwrite_user_selections()
    {
        $hash = Str::random(128);
        $trial = Trial::create([
            'hash' => $hash,
            'domain' => 'example.com',
            'subdomains' => ['user.selected.com'],
        ]);

        ApiUtils::shouldReceive('discover_public')
            ->once()
            ->with('example.com')
            ->andReturn(['subdomains' => ['discovered.com']]);

        $this->post(route('tools.discovery'), ['hash' => $hash]);
        $trial->refresh();
        $this->assertEquals(['user.selected.com'], $trial->subdomains);
    }
}
