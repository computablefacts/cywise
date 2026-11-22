<?php

use App\Enums\AssetTypesEnum;
use App\Models\Asset;

uses(Tests\TestCaseWithDb::class);

test('isIpAddressMissing returns false if type is IP', function () {
    $ip = '1.2.3.4';
    $asset = Asset::factory()->make([
        'asset' => $ip,
        'type' => AssetTypesEnum::IP,
    ]);
    expect($asset->isIpAddressMissing())->toBeFalse();
});

test('isIpAddressMissing follows CNAME records', function () {
    $asset = Asset::factory()->make([
        'asset' => 'www.example.com',
        'type' => AssetTypesEnum::DNS,
    ]);
    expect($asset->isIpAddressMissing(true))->toBeFalse();
});

test('isIpAddressMissing handles DNS resolution errors gracefully', function () {
    $asset = new Asset();
    $asset->type = AssetTypesEnum::DNS;
    $asset->asset = 'invalid-domain-that-should-fail-dns.test';

    expect($asset->isIpAddressMissing(true))->toBeTrue();
});

test('resolveIpAddresses handles dns failure', function () {
    $asset = Asset::factory()->make([
        'asset' => 'non-existent-domain-123456789.com',
        'type' => AssetTypesEnum::DNS,
    ]);

    $method = new ReflectionMethod(Asset::class, 'resolveIpAddresses');
    $method->setAccessible(true);

    $result = $method->invoke($asset, 'non-existent-domain-123456789.com');

    expect($result)->toBeArray()->toBeEmpty();
});

test('resolveIpAddresses returns ips if present', function () {
    $asset = Asset::factory()->make([
        'asset' => 'google.com',
        'type' => AssetTypesEnum::DNS,
    ]);

    $method = new ReflectionMethod(Asset::class, 'resolveIpAddresses');
    $method->setAccessible(true);

    $result = $method->invoke($asset, 'google.com');

    expect($result)->toBeArray()->not->toBeEmpty();
    foreach ($result as $ip) {
        expect(filter_var($ip, FILTER_VALIDATE_IP))->not->toBeFalse();
    }
});

test('resolveIpAddresses follows cname if no ips', function () {
    $domain = 'www.github.com';

    $asset = Asset::factory()->make([
        'asset' => $domain,
        'type' => AssetTypesEnum::DNS,
    ]);

    $method = new ReflectionMethod(Asset::class, 'resolveIpAddresses');
    $method->setAccessible(true);

    $result = $method->invoke($asset, $domain);

    expect($result)->toBeArray()->not->toBeEmpty();
});
