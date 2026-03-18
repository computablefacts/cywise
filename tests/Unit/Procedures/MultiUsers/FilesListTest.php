<?php

uses(\Sajya\Server\Testing\ProceduralRequests::class);

use App\Models\Collection;
use App\Models\File;

describe('files@list', function () {

    it('user1 can see files created by user2 in a shared collection', function () {
        // user2 creates a file in a shared collection
        asTenant1User2();
        $sharedCollection = Collection::factory()->create(['name' => 'shared-docs']);
        File::factory()->forCollection($sharedCollection)->create();

        // user1 queries: should see user2's file
        // BUG: returns 0 because the query filters on cb_files.created_by = user1->id
        asTenant1User();
        $response = $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('files@list');

        expect($response->json('result.files'))->toHaveCount(1);
    });

    it('user1 can see files from all tenant users in a shared collection', function () {
        // user2 creates a file in a shared collection
        asTenant1User2();
        $sharedCollection = Collection::factory()->create(['name' => 'shared-docs']);
        File::factory()->forCollection($sharedCollection)->create();

        // user1 also creates a file in the same shared collection
        asTenant1User();
        File::factory()->forCollection($sharedCollection)->create();

        // user1 queries: should see both files (their own + user2's)
        // BUG: returns 1 (only user1's file) because the query filters on cb_files.created_by = user1->id
        $response = $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('files@list');

        expect($response->json('result.files'))->toHaveCount(2);
    });

    it('user1 cannot see user2 private collection files', function () {
        // user2 creates a file in their own private collection
        $user2 = tenant1User2();
        $privateColUser2 = Collection::factory()->create([
            'name' => "privcol{$user2->id}",
            'created_by' => $user2->id,
        ]);
        File::factory()->forCollection($privateColUser2)->create(['created_by' => $user2->id]);

        // user1 queries: must NOT see user2's private file
        asTenant1User();
        $response = $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('files@list');

        expect($response->json('result.files'))->toHaveCount(0);
    });

    it('user1 cannot see files from a different tenant', function () {
        // tenant2 user creates a file
        asTenant2User();
        $collection = Collection::factory()->create(['name' => 'tenant2-docs']);
        File::factory()->forCollection($collection)->create();

        // user1 queries: must NOT see tenant2's files
        asTenant1User();
        $response = $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('files@list');

        expect($response->json('result.files'))->toHaveCount(0);
    });

});
