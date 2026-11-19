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
