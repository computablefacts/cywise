<?php

uses(\Sajya\Server\Testing\ProceduralRequests::class);

test('list tags depends on user', function () {
    $this->actingAs($this->userTenant1);
    $assetId = createAsset('www.example.com');
    createTag($assetId, 'tag1');
    createTag($assetId, 'tag2');

    $this->actingAs($this->userTenant2);
    $assetId = createAsset('www.example2.com');
    createTag($assetId, 'tag3');
    createTag($assetId, 'tag4');
    createTag($assetId, 'tag5');

    $this->actingAs($this->userTenant1);
    $this
        ->setRpcRoute('v2.private.rpc.endpoint')
        ->callProcedure('assets@listTags')
        ->assertExactJsonStructure([
            'id',
            'jsonrpc',
            'result' => [
                'tags',
            ],
        ])
        ->assertJsonFragment([
            'tags' => ['tag1', 'tag2'],
        ]);

    $this->actingAs($this->userTenant2);
    $this
        ->setRpcRoute('v2.private.rpc.endpoint')
        ->callProcedure('assets@listTags')
        ->assertExactJsonStructure([
            'id',
            'jsonrpc',
            'result' => [
                'tags',
            ],
        ])
        ->assertJsonFragment([
            'tags' => ['tag3', 'tag4', 'tag5'],
        ]);
});
