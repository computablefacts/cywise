<?php

namespace Tests\Unit\Models;

use App\Enums\AssetTypesEnum;
use App\Models\Asset;
use Tests\TestCaseWithDb;
use ReflectionMethod;

class AssetResolveIpOrderTest extends TestCaseWithDb
{
    /**
     * Test that resolveIpAddresses returns IPs directly if found, 
     * without resolving CNAMEs in that case.
     */
    public function test_resolveIpAddresses_returns_ips_if_present()
    {
        $asset = Asset::factory()->make([
            'asset' => 'google.com',
            'type' => AssetTypesEnum::DNS,
        ]);

        $method = new ReflectionMethod(Asset::class, 'resolveIpAddresses');
        $method->setAccessible(true);

        $result = $method->invoke($asset, 'google.com');

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        foreach ($result as $ip) {
            $this->assertTrue(filter_var($ip, FILTER_VALIDATE_IP) !== false, "Invalid IP: $ip");
        }
    }

    /**
     * Test that resolveIpAddresses follows CNAME if no A/AAAA records are present.
     */
    public function test_resolveIpAddresses_follows_cname_if_no_ips()
    {
        // On cherche un domaine qui est typiquement un CNAME pur sans record A direct
        // Souvent les sous-domaines www ou des alias de CDN
        $domain = 'www.github.com';
        
        $asset = Asset::factory()->make([
            'asset' => $domain,
            'type' => AssetTypesEnum::DNS,
        ]);

        $method = new ReflectionMethod(Asset::class, 'resolveIpAddresses');
        $method->setAccessible(true);

        $result = $method->invoke($asset, $domain);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }
}
