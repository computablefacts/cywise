<?php

namespace Tests\Unit\Models;

use App\Enums\AssetTypesEnum;
use App\Models\Asset;
use Tests\TestCaseWithDb;
use ReflectionMethod;

class AssetResolveIpTest extends TestCaseWithDb
{
    public function test_resolveIpAddresses_handles_dns_failure()
    {
        $asset = Asset::factory()->make([
            'asset' => 'non-existent-domain-123456789.com',
            'type' => AssetTypesEnum::DNS,
        ]);

        $method = new ReflectionMethod(Asset::class, 'resolveIpAddresses');
        $method->setAccessible(true);

        // Cette ligne devrait lever TypeError avant le fix si dns_get_record retourne false
        $result = $method->invoke($asset, 'non-existent-domain-123456789.com');

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}
