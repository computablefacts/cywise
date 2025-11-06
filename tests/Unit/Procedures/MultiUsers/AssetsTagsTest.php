<?php

uses(\Sajya\Server\Testing\ProceduralRequests::class);
uses(\Tests\AssetsProcedureHelpers::class);

test('list tags depends on user', function () {
    $this->actingAs($this->userTenant1);
    $assetId = $this->createAsset('www.example.com');
    $this->createTag($assetId, 'tag1');
    $this->createTag($assetId, 'tag2');

    $this->actingAs($this->userTenant2);
    $assetId = $this->createAsset('www.example2.com');
    $this->createTag($assetId, 'tag3');
    $this->createTag($assetId, 'tag4');
    $this->createTag($assetId, 'tag5');

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
