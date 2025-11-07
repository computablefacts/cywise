<?php

uses(\Sajya\Server\Testing\ProceduralRequests::class);

test('list tags depends on user', function () {
    asTenant1User();
    $asset = createAsset('www.example.com');
    createTag($asset, 'tag1');
    createTag($asset, 'tag2');

    asTenant2User();
    $asset = createAsset('www.example2.com');
    createTag($asset, 'tag3');
    createTag($asset, 'tag4');
    createTag($asset, 'tag5');

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
