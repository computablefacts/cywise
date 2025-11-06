<?php

uses(\Sajya\Server\Testing\ProceduralRequests::class);
uses(\Tests\AssetsProcedureHelpers::class);

test('create an asset and add atag', function ($asset) {
    $this->actingAs($this->userTenant1);

    $assetId = $this->createAsset($asset);

    $this
        ->setRpcRoute('v2.private.rpc.endpoint')
        ->callProcedure('assets@tag', [
            'asset_id' => $assetId,
            'tag' => 'tag1',
        ])
        ->assertExactJsonStructure([
            'id',
            'jsonrpc',
            'result' => [
                'tag' => [
                    'asset_id',
                    'created_at',
                    'created_by',
                    'id',
                    'tag',
                    'updated_at',
                ],
            ],
        ])
        ->assertJsonFragment([
            'asset_id' => $assetId,
            'tag' => 'tag1',
        ]);
})->with([
    'Valid DNS' => ['www.example.com'],
    'Valid IP' => ['93.184.215.14'],
    'Valid CIDR' => ['255.255.255.255/32'],
]);

test('remove tag', function ($asset) {
    $this->actingAs($this->userTenant1);

    $assetId = $this->createAsset($asset);
    $tagId = $this->createTag($assetId, 'tag1');

    $this
        ->setRpcRoute('v2.private.rpc.endpoint')
        ->callProcedure('assets@untag', [
            'asset_id' => $assetId,
            'tag_id' => $tagId,
        ])
        ->assertExactJsonStructure([
            'id',
            'jsonrpc',
            'result' => [
                'msg',
            ],
        ])
        ->assertJsonFragment([
            'msg' => 'The tag tag1 has been removed.',
        ]);
})->with([
    'Valid DNS' => ['www.example.com'],
    'Valid IP' => ['93.184.215.14'],
    'Valid CIDR' => ['255.255.255.255/32'],
]);

test('cannot remove anon existent tag', function () {
    $this->actingAs($this->userTenant1);

    $assetId = $this->createAsset('www.example.com');

    $this
        ->setRpcRoute('v2.private.rpc.endpoint')
        ->callProcedure('assets@untag', [
            'asset_id' => $assetId,
            'tag_id' => 99999,
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
                'tag_id' => ['The selected tag id is invalid.'],
            ],
        ]);
});
