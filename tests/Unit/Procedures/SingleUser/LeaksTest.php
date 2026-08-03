<?php

use App\Models\Asset;
use App\Models\Leak;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Sajya\Server\Testing\ProceduralRequests;

uses(ProceduralRequests::class);

test('leaks list can be filtered by asset name', function () {

    /** @var Tenant $tenant */
    $tenant = Tenant::create(['name' => 'Test Tenant']);
    /** @var User $user */
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    Auth::login($user);

    // Create an asset
    $asset = Asset::factory()->create([
        'asset' => 'example.com',
        'type' => \App\Enums\AssetTypesEnum::DNS,
        'created_by' => $user->id,
    ]);
    $asset->tld();

    // Create another asset
    $otherAsset = Asset::factory()->create([
        'asset' => 'other.org',
        'type' => \App\Enums\AssetTypesEnum::DNS,
        'created_by' => $user->id,
    ]);
    $otherAsset->tld();

    Leak::create([
        'email' => 'user@example.com',
        'website' => 'http://example.com/login',
        'password' => 'secret123',
        'created_by' => $user->id,
        'created_at' => now(),
    ]);

    Leak::create([
        'email' => 'admin@other.org',
        'website' => 'https://other.org',
        'password' => 'pass456',
        'created_by' => $user->id,
        'created_at' => now(),
    ]);

    $this->setRpcRoute('v2.private.rpc.endpoint')
        ->callProcedure('leaks@list', ['asset' => 'example.com'])
        ->assertJsonCount(1, 'result.leaks')
        ->assertJsonFragment(['email' => 'user@example.com']);

    $this->setRpcRoute('v2.private.rpc.endpoint')
        ->callProcedure('leaks@list', ['asset' => 'other.org'])
        ->assertJsonCount(1, 'result.leaks')
        ->assertJsonFragment(['email' => 'admin@other.org']);
});

test('leaks list can be filtered by asset_id', function () {

    /** @var Tenant $tenant */
    $tenant = Tenant::create(['name' => 'Test Tenant']);
    /** @var User $user */
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    Auth::login($user);

    $asset = Asset::factory()->create([
        'asset' => 'example.com',
        'type' => \App\Enums\AssetTypesEnum::DNS,
        'created_by' => $user->id,
    ]);
    $asset->tld();

    Leak::create([
        'email' => 'user@example.com',
        'website' => 'http://example.com/login',
        'password' => 'secret123',
        'created_by' => $user->id,
        'created_at' => now(),
    ]);

    $this->setRpcRoute('v2.private.rpc.endpoint')
        ->callProcedure('leaks@list', ['asset_id' => $asset->id])
        ->assertJsonCount(1, 'result.leaks')
        ->assertJsonFragment(['email' => 'user@example.com']);
});
