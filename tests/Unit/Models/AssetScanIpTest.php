<?php

use App\Enums\AssetTypesEnum;
use App\Models\Asset;
use App\Models\Port;
use App\Models\Scan;

uses(Tests\TestCaseWithDb::class);

test('ipAddresses returns scan IP if available for DNS asset', function () {
    $domain = 'scan-ip-test.example.com';
    $scanIp = '192.168.1.100';

    $asset = Asset::factory()->create([
        'asset' => $domain,
        'type' => AssetTypesEnum::DNS,
    ]);

    $scan = Scan::factory()->create([
        'asset_id' => $asset->id,
        'ports_scan_ends_at' => now(),
    ]);

    Port::factory()->create([
        'scan_id' => $scan->id,
        'ip' => $scanIp,
    ]);

    $method = new ReflectionMethod(Asset::class, 'ipAddresses');
    $method->setAccessible(true);
    $result = $method->invoke($asset);

    expect($result)->toBe([$scanIp]);
});

test('ipAddresses falls back to DNS and caches if no scan available', function () {
    Cache::spy();
    $domain = 'google.com'; // Use a real domain for DNS resolution

    $asset = Asset::factory()->create([
        'asset' => $domain,
        'type' => AssetTypesEnum::DNS,
    ]);

    // No scan created

    $method = new ReflectionMethod(Asset::class, 'ipAddresses');
    $method->setAccessible(true);
    $result = $method->invoke($asset);

    expect($result)->not->toBeEmpty();
    Cache::shouldHaveReceived('remember')->once();
});

test('ipAddresses scan lookup is not cached', function () {
    Cache::spy();
    $domain = 'scan-no-cache.example.com';
    $scanIp = '1.2.3.4';

    $asset = Asset::factory()->create([
        'asset' => $domain,
        'type' => AssetTypesEnum::DNS,
    ]);

    $scan = Scan::factory()->create([
        'asset_id' => $asset->id,
        'ports_scan_ends_at' => now(),
    ]);

    Port::factory()->create([
        'scan_id' => $scan->id,
        'ip' => $scanIp,
    ]);

    $method = new ReflectionMethod(Asset::class, 'ipAddresses');
    $method->setAccessible(true);
    
    // First call
    $result1 = $method->invoke($asset);
    expect($result1)->toBe([$scanIp]);

    // Second call
    $result2 = $method->invoke($asset);
    expect($result2)->toBe([$scanIp]);

    // remember should not be called for scan IP
    Cache::shouldNotHaveReceived('remember');
});
