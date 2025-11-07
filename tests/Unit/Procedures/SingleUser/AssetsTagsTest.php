<?php

uses(\Sajya\Server\Testing\ProceduralRequests::class);

test('create an asset and add atag', function ($asset) {
    asTenant1User();

    $assetId = createAsset($asset);

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
    'Valid IPv4' => ['93.184.215.14'],
    'Valid IPv6' => ['2001:bc8:701:1b:b283:feff:fed3:ebf1'],
    'Valid IPv4 CIDR' => ['157.54.9.25/28', null, 'RANGE'],
    'Valid IPv6 CIDR' => ['2001:bc8:701:1b:b283:feff:fed3:ebf1/122', null, 'RANGE'],
]);

test('remove tag', function ($asset) {
    asTenant1User();

    $assetId = createAsset($asset);
    $tagId = createTag($assetId, 'tag1');

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
    'Valid IPv4' => ['93.184.215.14'],
    'Valid IPv6' => ['2001:bc8:701:1b:b283:feff:fed3:ebf1'],
    'Valid IPv4 CIDR' => ['157.54.9.25/28', null, 'RANGE'],
    'Valid IPv6 CIDR' => ['2001:bc8:701:1b:b283:feff:fed3:ebf1/122', null, 'RANGE'],
]);

test('cannot remove a non existent tag', function () {
    asTenant1User();

    $assetId = createAsset('www.example.com');

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

it('lists tags', function () {
    asTenant1User();
    $assetId = createAsset('www.example.com');
    createTag($assetId, 'tag1');
    createTag($assetId, 'tag2');

    asTenant1User();
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

});
