<?php

use App\Models\Chunk;
use App\Models\Collection;
use App\Models\File;

uses(\Sajya\Server\Testing\ProceduralRequests::class);

describe('chunks@list', function () {

    it('user1 can see shared chunks created by user2 in the same tenant', function () {
        asTenant1User2();
        $collection = Collection::factory()->create(['name' => 'shared-docs']);
        $file = File::factory()->forCollection($collection)->create();
        Chunk::factory()->forFile($file)->create(['text' => 'shared chunk']);

        asTenant1User();
        $response = $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('chunks@list');

        expect($response->json('result.chunks'))->toHaveCount(1);
        expect($response->json('result.chunks.0.text'))->toBe('shared chunk');
    });

    it('user1 cannot see user2 private collection chunks', function () {
        $user2 = tenant1User2();

        $privateCollection = Collection::factory()->create([
            'name' => "privcol{$user2->id}",
            'created_by' => $user2->id,
        ]);
        $file = File::factory()->forCollection($privateCollection)->create(['created_by' => $user2->id]);
        Chunk::factory()->forFile($file)->create([
            'text' => 'private chunk',
            'created_by' => $user2->id,
        ]);

        asTenant1User();
        $response = $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('chunks@list');

        expect($response->json('result.chunks'))->toHaveCount(0);
    });

    it('user1 cannot see chunks from another tenant', function () {
        asTenant2User();
        $collection = Collection::factory()->create(['name' => 'tenant2-docs']);
        $file = File::factory()->forCollection($collection)->create();
        Chunk::factory()->forFile($file)->create(['text' => 'tenant2 chunk']);

        asTenant1User();
        $response = $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('chunks@list');

        expect($response->json('result.chunks'))->toHaveCount(0);
    });

    it('paginates shared chunks across the tenant', function () {
        asTenant1User2();
        $collection = Collection::factory()->create(['name' => 'shared-docs']);
        $file = File::factory()->forCollection($collection)->create();
        Chunk::factory()->forFile($file)->create(['page' => 1, 'text' => 'chunk a']);
        Chunk::factory()->forFile($file)->create(['page' => 2, 'text' => 'chunk b']);
        Chunk::factory()->forFile($file)->create(['page' => 3, 'text' => 'chunk c']);

        asTenant1User();
        $response = $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('chunks@list', [
                'page' => 1,
                'page_size' => 2,
            ]);

        expect($response->json('result.chunks'))->toHaveCount(2);
        expect($response->json('result.nb_pages'))->toBe(2);
    });
});

describe('chunks@update', function () {

    it('user1 can update a shared chunk created by user2 in the same tenant', function () {
        asTenant1User2();
        $collection = Collection::factory()->create(['name' => 'shared-docs']);
        $file = File::factory()->forCollection($collection)->create();
        $chunk = Chunk::factory()->forFile($file)->create(['text' => 'old text']);

        asTenant1User();
        $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('chunks@update', [
                'chunk_id' => $chunk->id,
                'value' => 'updated text',
            ])
            ->assertExactJsonStructure([
                'id',
                'jsonrpc',
                'result' => [
                    'msg',
                ],
            ])
            ->assertJsonFragment([
                'msg' => 'Your chunk will be updated soon!',
            ]);

        expect($chunk->fresh()->text)->toBe('updated text');
    });

    it('user1 cannot update user2 private collection chunk', function () {
        $user2 = tenant1User2();

        $privateCollection = Collection::factory()->create([
            'name' => "privcol{$user2->id}",
            'created_by' => $user2->id,
        ]);
        $file = File::factory()->forCollection($privateCollection)->create(['created_by' => $user2->id]);
        $chunk = Chunk::factory()->forFile($file)->create([
            'text' => 'private chunk',
            'created_by' => $user2->id,
        ]);

        asTenant1User();
        $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('chunks@update', [
                'chunk_id' => $chunk->id,
                'value' => 'blocked update',
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
                'message' => 'The chunk cannot be found.',
            ]);

        expect($chunk->fresh()->text)->toBe('private chunk');
    });
});

describe('chunks@delete', function () {

    it('user1 can delete a shared chunk created by user2 in the same tenant', function () {
        asTenant1User2();
        $collection = Collection::factory()->create(['name' => 'shared-docs']);
        $file = File::factory()->forCollection($collection)->create();
        $chunk = Chunk::factory()->forFile($file)->create(['text' => 'shared chunk']);

        asTenant1User();
        $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('chunks@delete', [
                'chunk_id' => $chunk->id,
            ])
            ->assertExactJsonStructure([
                'id',
                'jsonrpc',
                'result' => [
                    'msg',
                ],
            ])
            ->assertJsonFragment([
                'msg' => 'Your chunk will be deleted soon!',
            ]);

        expect($chunk->fresh()->is_deleted)->toBeTrue();
    });

    it('user1 cannot delete user2 private collection chunk', function () {
        $user2 = tenant1User2();

        $privateCollection = Collection::factory()->create([
            'name' => "privcol{$user2->id}",
            'created_by' => $user2->id,
        ]);
        $file = File::factory()->forCollection($privateCollection)->create(['created_by' => $user2->id]);
        $chunk = Chunk::factory()->forFile($file)->create([
            'text' => 'private chunk',
            'created_by' => $user2->id,
        ]);

        asTenant1User();
        $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('chunks@delete', [
                'chunk_id' => $chunk->id,
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
                'message' => 'The chunk cannot be found.',
            ]);

        expect($chunk->fresh()->is_deleted)->toBeFalse();
    });
});
