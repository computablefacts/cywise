<?php

use App\Enums\AssetTypesEnum;
use App\Models\Asset;
use App\Rules\IsValidDomain;
use App\Rules\IsValidIpAddress;

test('asset factory makes asset instance', function () {
    $asset = Asset::factory()->make();

    expect($asset)->toBeInstanceOf(Asset::class);
});

test('asset factory makes asset with attributes', function () {
    $asset = Asset::factory()->make([
        'asset' => 'mydomain.com',
        'type' => AssetTypesEnum::DNS,
    ]);

    expect($asset->asset)->toBe('mydomain.com')
        ->and($asset->type)->toBe(AssetTypesEnum::DNS);
});

test('asset factory makes asset with right type', function (string $assetValue, AssetTypesEnum $expectedType) {
    $asset = Asset::factory()->make([
        'asset' => $assetValue,
    ]);

    expect($asset->asset)->toBe($assetValue)
        ->and($asset->type)->toBe($expectedType);
})->with([
    'domain' => ['mydomain.com', AssetTypesEnum::DNS],
    'ip' => ['15.24.85.204', AssetTypesEnum::IP],
    'range' => ['15.24.85.0/24', AssetTypesEnum::RANGE],
]);

test('asset factory makes DNS asset type with corresponding asset value', function () {
    $asset = Asset::factory()->make([
        'type' => AssetTypesEnum::DNS,
    ]);

    expect(IsValidDomain::test($asset->asset))->toBeTrue();
});

test('asset factory makes IP asset type with corresponding asset value', function () {
    $asset = Asset::factory()->make([
        'type' => AssetTypesEnum::IP,
    ]);

    expect(IsValidIpAddress::test($asset->asset))->toBeTrue();
});

test('asset factory can create and persist asset', function () {
    asTenant1User();
    $asset = Asset::factory()->create();

    expect($asset)->toBeInstanceOf(Asset::class)
        ->and($asset->exists)->toBeTrue();
});

test('asset factory can create multiple assets', function () {
    asTenant1User();
    $assets = Asset::factory()->count(3)->create();

    expect($assets)->toHaveCount(3)
        ->each->toBeInstanceOf(Asset::class);
});

it('creates a monitored asset', function () {
    $alert = Asset::factory()->monitored()->create();

    expect($alert->is_monitored)->toBeTrue();
});

it('creates an unmonitored asset', function () {
    $alert = Asset::factory()->unmonitored()->create();

    expect($alert->is_monitored)->toBeFalse();
});
