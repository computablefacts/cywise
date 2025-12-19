<?php

use App\Models\Alert;
use App\Models\Asset;
use App\Models\AssetTag;
use App\Models\AssetTagHash;
use App\Models\Port;
use App\Models\Scan;

uses(\Sajya\Server\Testing\ProceduralRequests::class);

describe('assets@group', function () {

    it('creates a group from asset tags', function () {
        asTenant1User();

        $asset = Asset::factory()->create();
        AssetTag::factory([
            'tag' => 'tag1',
        ])->for($asset)->create();

        $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('assets@group', [
                'tags' => ['tag1'],
            ])
            ->assertExactJsonStructure([
                'id',
                'jsonrpc',
                'result' => [
                    'group' => [
                        'hash',
                        'tags',
                    ],
                ],
            ])
            ->assertJsonFragment([
                'tags' => ['tag1'],
            ]);
    });

    test('tags should exist', function () {
        asTenant1User();

        $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('assets@group', [
                'tags' => ['tag1'],
            ])
            ->assertExactJsonStructure([
                'id',
                'jsonrpc',
                'error' => [
                    'code',
                    'data',
                    'message',
                ],
            ])
            ->assertJsonFragments([
                [
                    'message' => 'Invalid params',
                ],
                [
                    'tags.0' => ['The selected tags.0 is invalid.'],
                ],
            ]);

    });
});

describe('assets@degroup', function () {

    it('deletes a group with one tag from its hash', function () {
        asTenant1User();

        $group = AssetTagHash::factory()->create();

        $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('assets@degroup', [
                'group' => $group->hash,
            ])
            ->assertExactJsonStructure([
                'id',
                'jsonrpc',
                'result' => [
                    'msg',
                ],
            ])
            ->assertJsonFragment([
                'msg' => "The group {$group->hash} has been disbanded!",
            ]);
    });

    it('deletes a group with two tags from its hash', function () {
        asTenant1User();

        AssetTag::factory(['tag' => 'tag1'])->create();
        AssetTag::factory(['tag' => 'tag2'])->create();

        $hash = 'myHash_45zef84erg';
        AssetTagHash::factory(['hash' => $hash, 'tag' => 'tag1'])->create();
        AssetTagHash::factory(['hash' => $hash, 'tag' => 'tag2'])->create();

        $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('assets@degroup', [
                'group' => $hash,
            ])
            ->assertExactJsonStructure([
                'id',
                'jsonrpc',
                'result' => [
                    'msg',
                ],
            ])
            ->assertJsonFragment([
                'msg' => "The group {$hash} has been disbanded!",
            ]);
    });
});

describe('assets@listGroups', function () {

    it('lists no groups when none exist', function () {
        asTenant1User();

        $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('assets@listGroups', [])
            ->assertExactJsonStructure([
                'id',
                'jsonrpc',
                'result' => [
                    'groups',
                ],
            ])
            ->assertJsonFragment([
                'groups' => [],
            ]);
    });

    it('lists a single group with one tag', function () {
        asTenant1User();

        $hash = 'single_hash_1';
        AssetTag::factory(['tag' => 'tag1'])->create();
        AssetTagHash::factory(['hash' => $hash, 'tag' => 'tag1'])->create();

        $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('assets@listGroups', [])
            ->assertExactJsonStructure([
                'id',
                'jsonrpc',
                'result' => [
                    'groups' => [
                        ['created_by_email', 'hash', 'tags', 'views'],
                    ],
                ],
            ])
            ->assertJsonFragment([
                'created_by_email' => tenant1User()->email,
                'hash' => $hash,
                'tags' => ['tag1'],
                'views' => 0,
            ]);
    });

    it('lists groups with multiple tags aggregated by hash', function () {
        asTenant1User();

        AssetTag::factory(['tag' => 'tag1'])->create();
        AssetTag::factory(['tag' => 'tag2'])->create();

        $hash = 'multi_hash_99';
        AssetTagHash::factory(['hash' => $hash, 'tag' => 'tag1'])->create();
        AssetTagHash::factory(['hash' => $hash, 'tag' => 'tag2'])->create();

        $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('assets@listGroups', [])
            ->assertExactJsonStructure([
                'id',
                'jsonrpc',
                'result' => [
                    'groups' => [
                        ['created_by_email', 'hash', 'tags', 'views'],
                    ],
                ],
            ])
            ->assertJsonFragment([
                'created_by_email' => tenant1User()->email,
                'hash' => $hash,
                'tags' => ['tag1', 'tag2'],
                'views' => 0,
            ]);
    });

    it('lists 2 groups', function () {
        asTenant1User();

        AssetTag::factory(['tag' => 'tag1'])->create();
        AssetTag::factory(['tag' => 'tag2'])->create();

        $hashTag1AndTag2 = 'hash_for_tag1_tag2';
        AssetTagHash::factory(['hash' => $hashTag1AndTag2, 'tag' => 'tag1'])->create();
        AssetTagHash::factory(['hash' => $hashTag1AndTag2, 'tag' => 'tag2'])->create();

        $hashTag1Only = 'hash_for_tag1_only';
        AssetTagHash::factory(['hash' => $hashTag1Only, 'tag' => 'tag1'])->create();

        $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('assets@listGroups', [])
            ->assertExactJsonStructure([
                'id',
                'jsonrpc',
                'result' => [
                    'groups' => [
                        ['created_by_email', 'hash', 'tags', 'views'],
                        ['created_by_email', 'hash', 'tags', 'views'],
                    ],
                ],
            ])
            ->assertJsonFragments([
                [
                    'created_by_email' => tenant1User()->email,
                    'hash' => $hashTag1AndTag2,
                    'tags' => ['tag1', 'tag2'],
                    'views' => 0,
                ], [
                    'created_by_email' => tenant1User()->email,
                    'hash' => $hashTag1Only,
                    'tags' => ['tag1'],
                    'views' => 0,
                ],
            ]);
    });

    it('avoid tag duplication in tags array', function () {
        asTenant1User();

        AssetTag::factory(['tag' => 'tag1'])->create();

        AssetTagHash::factory(['hash' => 'user1@tenant2.com', 'tag' => 'tag1'])->create();
        AssetTagHash::factory(['hash' => 'user1@tenant2.com', 'tag' => 'tag1'])->create();

        $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('assets@listGroups', [])
            ->assertExactJsonStructure([
                'id',
                'jsonrpc',
                'result' => [
                    'groups' => [
                        ['created_by_email', 'hash', 'tags', 'views'],
                    ],
                ],
            ])
            ->assertJsonFragments([
                [
                    'created_by_email' => tenant1User()->email,
                    'hash' => 'user1@tenant2.com',
                    'tags' => ['tag1'],
                    'views' => 0,
                ],
            ]);
    });

    it('avoid tag duplication in tags array with more complex cases', function () {
        asTenant1User();

        AssetTag::factory(['tag' => 'tag1'])->create();
        AssetTag::factory(['tag' => 'tag2'])->create();

        AssetTagHash::factory(['hash' => 'user1@tenant2.com', 'tag' => 'tag1'])->create();
        AssetTagHash::factory(['hash' => 'user1@tenant2.com', 'tag' => 'tag1'])->create();
        AssetTagHash::factory(['hash' => 'user1@tenant2.com', 'tag' => 'tag1'])->create();
        AssetTagHash::factory(['hash' => 'user1@tenant2.com', 'tag' => 'tag2'])->create();
        AssetTagHash::factory(['hash' => 'user1@tenant2.com', 'tag' => 'tag2'])->create();

        $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('assets@listGroups', [])
            ->assertExactJsonStructure([
                'id',
                'jsonrpc',
                'result' => [
                    'groups' => [
                        ['created_by_email', 'hash', 'tags', 'views'],
                    ],
                ],
            ])
            ->assertJsonFragments([
                [
                    'created_by_email' => tenant1User()->email,
                    'hash' => 'user1@tenant2.com',
                    'tags' => ['tag1', 'tag2'],
                    'views' => 0,
                ],
            ]);
    });
});

describe('assets@getGroup', function () {

    it('returns one group information', function () {
        asTenant1User();

        $hash = 'single_hash_1';
        AssetTag::factory(['tag' => 'tag1'])->create();
        AssetTagHash::factory(['hash' => $hash, 'tag' => 'tag1'])->create();

        $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('assets@getGroup', ['group' => $hash])
            ->assertExactJsonStructure([
                'id',
                'jsonrpc',
                'result' => [
                    'group' => [
                        'hash',
                        'tags',
                        'views',
                    ],
                ],
            ])
            ->assertJsonFragment([
                'hash' => $hash,
                'tags' => ['tag1'],
                'views' => 0,
            ]);
    });
});

describe('assets@assetsInGroup', function () {

    it('returns one asset in one group', function () {
        asTenant1User();

        $hash = 'single_hash_1';
        $asset = Asset::factory([
            'asset' => 'prod.mydomain.com',
        ])->monitored()->create();
        AssetTag::factory(['tag' => 'tag1'])->for($asset)->create();
        AssetTagHash::factory(['hash' => $hash, 'tag' => 'tag1'])->create();

        $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('assets@assetsInGroup', ['group' => $hash])
            ->assertExactJsonStructure([
                'id',
                'jsonrpc',
                'result' => [
                    'assets' => [
                        ['asset', 'status', 'tags', 'tld', 'type', 'uid'],
                    ],
                ],
            ])
            ->assertJsonFragment([
                'asset' => 'prod.mydomain.com',
                'tld' => 'mydomain.com',
                'type' => 'DNS',
            ]);
    });

    it('returns two assets in one group', function () {
        asTenant1User();

        $hash = 'single_hash_1';
        $asset1 = Asset::factory([
            'asset' => 'prod.mydomain.com',
        ])->monitored()->create();
        AssetTag::factory(['tag' => 'tag1'])->for($asset1)->create();
        $asset2 = Asset::factory([
            'asset' => 'staging.mydomain.com',
        ])->monitored()->create();
        AssetTag::factory(['tag' => 'tag1'])->for($asset2)->create();
        AssetTagHash::factory(['hash' => $hash, 'tag' => 'tag1'])->create();

        $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('assets@assetsInGroup', ['group' => $hash])
            ->assertExactJsonStructure([
                'id',
                'jsonrpc',
                'result' => [
                    'assets' => [
                        ['asset', 'status', 'tags', 'tld', 'type', 'uid'],
                        ['asset', 'status', 'tags', 'tld', 'type', 'uid'],
                    ],
                ],
            ])
            ->assertJsonFragments([
                [
                    'asset' => 'prod.mydomain.com',
                    'tld' => 'mydomain.com',
                    'type' => 'DNS',
                ], [
                    'asset' => 'staging.mydomain.com',
                    'tld' => 'mydomain.com',
                    'type' => 'DNS',
                ],
            ]);
    });

    it('returns only assets in the group', function () {
        asTenant1User();

        $assetsTag1 = Asset::factory(7)->monitored()->create();
        $assetsTag1->each(function ($asset) {
            AssetTag::factory(['tag' => 'tag1'])->for($asset)->create();
        });
        $assetsTag2 = Asset::factory(5)->monitored()->create();
        $assetsTag2->each(function ($asset) {
            AssetTag::factory(['tag' => 'tag2'])->for($asset)->create();
        });
        $assetsTag1AndTag2 = Asset::factory(3)->monitored()->create();
        $assetsTag1AndTag2->each(function ($asset) {
            AssetTag::factory(['tag' => 'tag1'])->for($asset)->create();
            AssetTag::factory(['tag' => 'tag2'])->for($asset)->create();
        });

        $hash = 'single_hash_1';
        AssetTagHash::factory(['hash' => $hash, 'tag' => 'tag1'])->create();

        $response = $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('assets@assetsInGroup', ['group' => $hash])
            ->assertExactJsonStructure([
                'id',
                'jsonrpc',
                'result' => [
                    'assets',
                ],
            ]);

        expect($response->json('result.assets'))->toBeArray()->toHaveCount(10);
    });

    it('returns assets in a group with 2 tags', function () {
        asTenant1User();

        $assetsTag1 = Asset::factory(7)->monitored()->create();
        $assetsTag1->each(function ($asset) {
            AssetTag::factory(['tag' => 'tag1'])->for($asset)->create();
        });
        $assetsTag2 = Asset::factory(5)->monitored()->create();
        $assetsTag2->each(function ($asset) {
            AssetTag::factory(['tag' => 'tag2'])->for($asset)->create();
        });
        $assetsTag1AndTag2 = Asset::factory(3)->monitored()->create();
        $assetsTag1AndTag2->each(function ($asset) {
            AssetTag::factory(['tag' => 'tag1'])->for($asset)->create();
            AssetTag::factory(['tag' => 'tag2'])->for($asset)->create();
        });

        $hash = 'single_hash_1';
        AssetTagHash::factory(['hash' => $hash, 'tag' => 'tag1'])->create();
        AssetTagHash::factory(['hash' => $hash, 'tag' => 'tag2'])->create();

        $response = $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('assets@assetsInGroup', ['group' => $hash])
            ->assertExactJsonStructure([
                'id',
                'jsonrpc',
                'result' => [
                    'assets',
                ],
            ]);

        expect($response->json('result.assets'))->toBeArray()->toHaveCount(15);
    });
});

describe('assets@vulnerabilitiesInGroup', function () {
    it('returns vulnerabilities for assets in a group', function () {
        asTenant1User();

        $assetsTag1 = Asset::factory(2)->monitored()->create();
        $assetsTag1->each(function ($asset) {
            AssetTag::factory(['tag' => 'tag1'])->for($asset)->create();
            Alert::factory(3)->for(
                Port::factory()->for(
                    Scan::factory()->for($asset)->vulnsScanEnded()->create()
                )->create()
            )->levelHigh()->create();
        });

        $hash = 'single_hash_1';
        AssetTagHash::factory(['hash' => $hash, 'tag' => 'tag1'])->create();

        $response = $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('assets@vulnerabilitiesInGroup', ['group' => $hash])
            ->assertExactJsonStructure([
                'id',
                'jsonrpc',
                'result' => [
                    'vulnerabilities',
                ],
            ]);

        expect($response->json('result.vulnerabilities'))->toBeArray()->toHaveCount(6);
    });

    it('avoid vulnerabilities duplication when sharing same tag twice', function () {
        asTenant1User();

        $assetsTag1 = Asset::factory(2)->monitored()->create();
        $assetsTag1->each(function ($asset) {
            AssetTag::factory(['tag' => 'tag1'])->for($asset)->create();
            Alert::factory(3)->for(
                Port::factory()->for(
                    Scan::factory()->for($asset)->vulnsScanEnded()->create()
                )->create()
            )->levelHigh()->create();
        });

        expect(Asset::withoutGlobalScope('tenant_scope')->count())->toBe(2);

        AssetTagHash::factory(['hash' => 'user1@tenant2.com', 'tag' => 'tag1'])->create();
        AssetTagHash::factory(['hash' => 'user1@tenant2.com', 'tag' => 'tag1'])->create();

        // We should have only 3 assets but AssetTagHash::factory creates a 3rd and a 4th one...
        expect(Asset::withoutGlobalScope('tenant_scope')->count())->toBe(4);

        $response = $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('assets@vulnerabilitiesInGroup', ['group' => 'user1@tenant2.com'])
            ->assertExactJsonStructure([
                'id',
                'jsonrpc',
                'result' => [
                    'vulnerabilities',
                ],
            ]);

        expect($response->json('result.vulnerabilities'))->toBeArray()->toHaveCount(6);
    });

});
