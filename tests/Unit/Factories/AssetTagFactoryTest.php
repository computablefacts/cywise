<?php

use App\Models\Asset;
use App\Models\AssetTag;
use App\Models\User;

test('it creates an asset tag with default values', function () {
    asTenant1User();
    $assetTag = AssetTag::factory()->create();

    expect($assetTag)->toBeInstanceOf(AssetTag::class)
        ->and($assetTag->asset_id)->not->toBeNull()
        ->and($assetTag->asset)->toBeInstanceOf(Asset::class)
        ->and($assetTag->tag)->toBeString()
        ->and($assetTag->created_by)->not->toBeNull()
        ->and($assetTag->created_by)->toBe(tenant1User()->id)
        ->and($assetTag->creator)->toBeInstanceOf(User::class);
});

test('it creates an asset tag with related asset', function () {
    $asset = Asset::factory()->create();
    $assetTag = AssetTag::factory()->for($asset)->create();

    expect($assetTag->asset_id)->toBe($asset->id)
        ->and($assetTag->asset)->toBeInstanceOf(Asset::class);
});

test('it creates an asset tag with custom tag', function () {
    $customTag = 'production';
    $assetTag = AssetTag::factory()->create(['tag' => $customTag]);

    expect($assetTag->tag)->toBe($customTag);
});

test('it creates an asset tag with specific user', function () {
    $user = User::factory()->create();
    $assetTag = AssetTag::factory()->create(['created_by' => $user->id]);

    expect($assetTag->created_by)->toBe($user->id);
});

test('it creates multiple asset tags', function () {
    $assetTags = AssetTag::factory()->count(3)->create();

    expect($assetTags)->toHaveCount(3)
        ->each->toBeInstanceOf(AssetTag::class);
});

test('it generates random tag using faker', function () {
    $assetTag1 = AssetTag::factory()->make();
    $assetTag2 = AssetTag::factory()->make();

    expect($assetTag1->tag)->toBeString()
        ->and($assetTag2->tag)->toBeString()
        ->and($assetTag2->tag)->not->toBe($assetTag1->tag);
});
