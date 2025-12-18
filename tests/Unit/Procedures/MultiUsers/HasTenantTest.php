<?php

namespace Tests\Unit;

use App\Models\Alert;
use App\Models\Asset;
use App\Models\AssetTag;
use App\Models\AssetTagHash;
use App\Models\Port;
use App\Models\Scan;

test('asset global scope filters by created_by user tenant', function () {
    asTenant1User();
    Asset::factory()->create();

    asTenant2User();
    Asset::factory(2)->create();

    asTenant1User();

    expect(Asset::count())->toBe(1);
});

test('asset scope filters by customer_id when present', function () {})->todo('We should remove customer_id');

test('asset created_by relationship returns correct user', function () {
    asTenant1User();
    $asset = Asset::factory()->create();

    expect($asset->createdBy()->id)->toBe(tenant1User()->id);
});

test('users with the same tenant see all assets', function () {
    asTenant1User();
    $asset1 = Asset::factory()->create();
    asTenant1User2();
    $asset2 = Asset::factory()->create();

    asTenant1User();
    expect($asset1->createdBy()->id)->toBe(tenant1User()->id);
    expect(Asset::count())->toBe(2);
    asTenant1User2();
    expect($asset2->createdBy()->id)->toBe(tenant1User2()->id);
    expect(Asset::count())->toBe(2);
});

test("shared assets should be with tenant's ones", function () {
    // force tenant2user to be created
    asTenant2User();
    $tenant2UserEmail = tenant2User()->email;
    // 1 asset for tenant2User
    $asset = Asset::factory()->monitored()->create();

    asTenant1User();
    // 1 asset with 'tag1'
    $asset = Asset::factory()->monitored()->create();
    AssetTag::factory(['tag' => 'tag1', 'asset_id' => $asset->id])->create();

    expect(Asset::count())->toBe(1);

    // 1 asset with 'tag2'
    $asset = Asset::factory()->monitored()->create();
    AssetTag::factory(['tag' => 'tag2', 'asset_id' => $asset->id])->create();

    expect(Asset::count())->toBe(2);

    // Share assets with tag1 to tenant2User
    AssetTagHash::factory([
        'hash' => $tenant2UserEmail,
        'tag' => 'tag1',
    ])->create();

    // We should have only 3 assets but AssetTagHash::factory creates a 4th one...
    expect(Asset::withoutGlobalScope('tenant_scope')->count())->toBe(4);

    asTenant2User();
    expect(Asset::count())->toBe(2);
});

test("shared assets should be from all users of sharing user's tenant", function () {
    // force tenant2user to be created
    asTenant2User();
    $tenant2UserEmail = tenant2User()->email;

    asTenant1User();
    // 1 asset with 'tag1'
    $asset = Asset::factory()->monitored()->create();
    AssetTag::factory(['tag' => 'tag1', 'asset_id' => $asset->id])->create();

    expect(Asset::count())->toBe(1);

    // 1 asset with 'tag2'
    $asset = Asset::factory()->monitored()->create();
    AssetTag::factory(['tag' => 'tag2', 'asset_id' => $asset->id])->create();

    expect(Asset::count())->toBe(2);

    asTenant1User2();
    // 1 asset with 'tag1'
    $asset = Asset::factory()->monitored()->create();
    AssetTag::factory(['tag' => 'tag1', 'asset_id' => $asset->id])->create();

    expect(Asset::count())->toBe(3);

    // Share assets with tag1 to tenant2User
    AssetTagHash::factory([
        'hash' => $tenant2UserEmail,
        'tag' => 'tag1',
    ])->create();

    // We should have only 3 assets but AssetTagHash::factory creates a 4th one...
    expect(Asset::withoutGlobalScope('tenant_scope')->count())->toBe(4);

    // dump('---- Asset::withoutGlobalScope(tenant_scope)->get()->toArray() ----');
    // dump(Asset::withoutGlobalScope('tenant_scope')->get()->toArray());

    // dump('---- AssetTag::withoutGlobalScope(tenant_scope)->get()->toArray() ----');
    // dump(AssetTag::withoutGlobalScope('tenant_scope')->get()->toArray());

    // dump('---- Asset::query()->toSql() ----');
    // dump(Asset::query()->toSql());

    asTenant2User();
    expect(Asset::count())->toBe(2);
});
