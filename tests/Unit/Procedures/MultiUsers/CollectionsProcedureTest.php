<?php

use App\Models\Collection;

uses(\Sajya\Server\Testing\ProceduralRequests::class);

describe('collections@list', function () {

    it('user1 can see shared collections created by user2 in the same tenant', function () {
        asTenant1User2();
        Collection::factory()->create(['name' => 'shared-docs']);

        asTenant1User();
        $response = $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('collections@list');

        expect($response->json('result.collections'))->toHaveCount(1);
        expect($response->json('result.collections.0.name'))->toBe('shared-docs');
    });

    it('user1 cannot see user2 private collection', function () {
        $user2 = tenant1User2();

        Collection::factory()->create([
            'name' => "privcol{$user2->id}",
            'created_by' => $user2->id,
        ]);

        asTenant1User();
        $response = $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('collections@list');

        expect($response->json('result.collections'))->toHaveCount(0);
    });

    it('user1 cannot see collections from another tenant', function () {
        asTenant2User();
        Collection::factory()->create(['name' => 'tenant2-docs']);

        asTenant1User();
        $response = $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('collections@list');

        expect($response->json('result.collections'))->toHaveCount(0);
    });

    it('paginates shared collections across the tenant', function () {
        asTenant1User2();
        Collection::factory()->create(['name' => 'shared-a']);
        Collection::factory()->create(['name' => 'shared-b']);
        Collection::factory()->create(['name' => 'shared-c']);

        asTenant1User();
        $response = $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('collections@list', [
                'page' => 1,
                'page_size' => 2,
            ]);

        expect($response->json('result.collections'))->toHaveCount(2);
        expect($response->json('result.nb_pages'))->toBe(2);
    });
});

describe('collections@update', function () {

    it('user1 can update a shared collection created by user2 in the same tenant', function () {
        asTenant1User2();
        $collection = Collection::factory()->create([
            'name' => 'shared-docs',
            'priority' => 0,
        ]);

        asTenant1User();
        $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('collections@update', [
                'collection_id' => $collection->id,
                'priority' => 7,
            ])
            ->assertExactJsonStructure([
                'id',
                'jsonrpc',
                'result' => [
                    'msg',
                ],
            ])
            ->assertJsonFragment([
                'msg' => 'Your collection will be updated soon!',
            ]);

        expect($collection->fresh()->priority)->toBe(7);
    });

    it('user1 cannot update user2 private collection', function () {
        $user2 = tenant1User2();
        $privateCollection = Collection::factory()->create([
            'name' => "privcol{$user2->id}",
            'created_by' => $user2->id,
        ]);

        asTenant1User();
        $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('collections@update', [
                'collection_id' => $privateCollection->id,
                'priority' => 3,
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
            ->assertJsonFragment([
                'message' => 'The collection cannot be found.',
            ]);

        expect($privateCollection->fresh()->priority)->toBe(0);
    });
});

describe('collections@delete', function () {

    it('user1 can delete a shared collection created by user2 in the same tenant', function () {
        asTenant1User2();
        $collection = Collection::factory()->create([
            'name' => 'shared-docs',
        ]);

        asTenant1User();
        $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('collections@delete', [
                'collection_id' => $collection->id,
            ])
            ->assertExactJsonStructure([
                'id',
                'jsonrpc',
                'result' => [
                    'msg',
                ],
            ])
            ->assertJsonFragment([
                'msg' => 'Your collection will be deleted soon!',
            ]);

        expect($collection->fresh()->is_deleted)->toBeTrue();
    });

    it('user1 cannot delete user2 private collection', function () {
        $user2 = tenant1User2();
        $privateCollection = Collection::factory()->create([
            'name' => "privcol{$user2->id}",
            'created_by' => $user2->id,
        ]);

        asTenant1User();
        $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('collections@delete', [
                'collection_id' => $privateCollection->id,
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
            ->assertJsonFragment([
                'message' => 'The collection cannot be found.',
            ]);

        expect($privateCollection->fresh()->is_deleted)->toBeFalse();
    });
});
