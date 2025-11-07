<?php

uses(\Sajya\Server\Testing\ProceduralRequests::class);

test('list tags depends on user', function () {
    asTenant1User();
    $assetId = createAsset('www.example.com');
    createTag($assetId, 'tag1');
    createTag($assetId, 'tag2');

    asTenant2User();
    $assetId = createAsset('www.example2.com');
    createTag($assetId, 'tag3');
    createTag($assetId, 'tag4');
    createTag($assetId, 'tag5');

    asTenant1User()
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

    asTenant2User();
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
