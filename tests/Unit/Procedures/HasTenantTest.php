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
    Asset::factory()->create();

    asTenant1User();

    expect(Asset::count())->toBe(1);
});

test('asset scope filters by customer_id when present', function () {})->todo('Not yet implemented');

test('asset created_by relationship returns correct user', function () {
    asTenant1User();
    $asset = Asset::factory()->create();

    expect($asset->createdBy()->id)->toBe(tenant1User()->id);
});

test('users with the same tenant see all assets', function () {
    asTenant1User();
    Asset::factory()->create();
    asTenant1User2();
    Asset::factory()->create();

    asTenant1User();
    expect(Asset::count())->toBe(2);
    asTenant1User2();
    expect(Asset::count())->toBe(2);
});

test('shared assets should be with tenant\'s ones', function () {
    // force tenant2user to be created
    asTenant2User();

    asTenant1User();
    // 1 high alert for an asset with 'tag1'
    $asset = Asset::factory()->monitored()->create();
    AssetTag::factory(['tag' => 'tag1', 'asset_id' => $asset->id])->create();
    Alert::factory()->for(
        Port::factory()->for(
            Scan::factory()->for($asset)->vulnsScanEnded()->create()
        )->create()
    )->levelHigh()->create();

    expect(Asset::count())->toBe(1);

    // 2 high alerts for an asset with 'tag2'
    $asset = Asset::factory()->monitored()->create();
    AssetTag::factory(['tag' => 'tag2', 'asset_id' => $asset->id])->create();
    Alert::factory(2)->for(
        Port::factory()->for(
            Scan::factory()->for($asset)->vulnsScanEnded()->create()
        )->create()
    )->levelHigh()->create();

    expect(Asset::count())->toBe(2);

    // dump('---- Asset::withoutGlobalScope(tenant_scope)->get()->toArray() ----');
    // dump(Asset::withoutGlobalScope('tenant_scope')->get()->toArray());

    // dump('---- tenant2User()->email ----');
    // dump(tenant2User()->email);

    // Share asset with tag1 to tenant2User
    AssetTagHash::factory([
        'hash' => tenant2User()->email,
        'tag' => 'tag1',
    ])->create();

    // dump('---- AssetTagHash::withoutGlobalScope(tenant_scope)->get()->toArray() ----');
    // dump(AssetTagHash::withoutGlobalScope('tenant_scope')->get()->toArray());

    dump('---- tenant1User()->id ----');
    dump(tenant1User()->id);
    // dump('---- Asset::withoutGlobalScope(tenant_scope)->get()->toArray() ----');
    // dump(Asset::withoutGlobalScope('tenant_scope')->get()->toArray());
    // dump('---- Asset::query()->toSql() ----');
    // dump(Asset::query()->toSql());

    // dump(Asset::all()->toArray());
    dump('---- tenant2User()->id ----');
    dump(tenant2User()->id);

    asTenant2User();
    dump('---- Asset::all()->toArray() ----');
    dump(Asset::all()->toArray());
    dump('---- Asset::query()->toSql() ----');
    dump(Asset::query()->toSql());
    expect(Asset::count())->toBe(1);
});
