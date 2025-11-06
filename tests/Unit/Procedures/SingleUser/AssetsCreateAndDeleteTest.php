<?php

uses(\Sajya\Server\Testing\ProceduralRequests::class);
uses(\Tests\AssetsProcedureHelpers::class);

test('create and delete valid asset', function ($asset, $tld, $type) {
    $this->actingAs($this->userTenant1);

    // Create
    $response = $this
        ->setRpcRoute('v2.private.rpc.endpoint')
        ->callProcedure('assets@create', [
            'asset' => $asset,
            'watch' => false,
        ])
        ->assertExactJsonStructure([
            'id',
            'jsonrpc',
            'result' => [
                'asset' => [
                    'asset',
                    'status',
                    'tags',
                    'tld',
                    'type',
                    'uid',
                ],
            ],
        ])
        ->assertJsonFragment([
            'asset' => $asset,
            'status' => 'invalid',
            'tags' => [],
            'tld' => $tld,
            'type' => $type,
        ]);

    // Vérifier que l'actif a bien été créé dans la base de données
    $this->assertDatabaseHas('am_assets', [
        'asset' => $asset,
        'tld' => $tld,
        'type' => $type,
    ]);

    $assetId = $response->json('result.asset.uid');

    // Delete
    $this
        ->setRpcRoute('v2.private.rpc.endpoint')
        ->callProcedure('assets@delete', [
            'asset_id' => $assetId,
        ])
        ->assertExactJsonStructure([
            'id',
            'jsonrpc',
            'result' => [
                'msg',
            ],
        ])
        ->assertJsonFragment([
            'result' => [
                'msg' => "$asset has been removed.",
            ],
        ]);

    // Vérifier que l'actif a bien été supprimé de la base de données
    $this->assertDatabaseMissing('am_assets', [
        'asset' => $asset,
        'tld' => $tld,
        'type' => $type,
    ]);
})->with([
    'Valid DNS' => ['www.example.com', 'example.com', 'DNS'],
    'Valid IP' => ['93.184.215.14', null, 'IP'],
    'Valid CIDR' => ['255.255.255.255/32', null, 'RANGE'],
]);

test('invalid assets are not created', function ($asset) {
    $this->actingAs($this->userTenant1);

    $this
        ->setRpcRoute('v2.private.rpc.endpoint')
        ->callProcedure('assets@create', [
            'asset' => $asset,
            'watch' => false,
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
            'message' => "Invalid asset : $asset",
        ]);
})->with([
    'Just a string' => ['invalid_asset'],
    'Not a valid domain' => ['www+example+com'],
    'Wrong IP address' => ['18.25.36.999'],
    'Localhost IPv4 address' => ['127.0.0.1'],
    'Wrong CIDR notation' => ['1.2.3.4/36'],
]);

test('cannot delete an unknown asset id', function () {
    $this->actingAs($this->userTenant1);

    $this
        ->setRpcRoute('v2.private.rpc.endpoint')
        ->callProcedure('assets@delete', [
            'asset_id' => 12345,
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
                'asset_id' => ['The selected asset id is invalid.'],
            ],
        ]);
});

test('cannot delete monitored asset', function ($asset, $tld, $type) {
    $this->actingAs($this->userTenant1);

    $assetId = $this->createAsset($asset, true);

    // Delete
    $this
        ->setRpcRoute('v2.private.rpc.endpoint')
        ->callProcedure('assets@delete', [
            'asset_id' => $assetId,
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
            'message' => 'Deletion not allowed, asset is monitored.',
        ]);
})->with([
    'Valid DNS' => ['www.example.com', 'example.com', 'DNS'],
    'Valid IP' => ['93.184.215.14', null, 'IP'],
    'Valid CIDR' => ['255.255.255.255/32', null, 'RANGE'],
]);
