<?php

uses(\Sajya\Server\Testing\ProceduralRequests::class);

test('create and delete valid asset', function ($asset, $tld, $type) {
    asTenant1User();

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
        'discovery_id' => null,
        'prev_scan_id' => null,
        'cur_scan_id' => null,
        'next_scan_id' => null,
        'is_monitored' => false,
        'created_by' => tenant1UserId(),
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
    'Valid IPv4' => ['93.184.215.14', null, 'IP'],
    'Valid IPv6' => ['2001:bc8:701:1b:b283:feff:fed3:ebf1', null, 'IP'],
    'Valid IPv4 CIDR' => ['157.54.9.25/28', null, 'RANGE'],
    'Valid IPv6 CIDR' => ['2001:bc8:701:1b:b283:feff:fed3:ebf1/122', null, 'RANGE'],
]);

test('invalid assets are not created', function ($asset) {
    asTenant1User();

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
    'Reserved range IPv4' => ['169.254.0.0/24'],
    'Reserved range IPv6' => ['FE80::/120'],
    'Just a string' => ['invalid_asset'],
    'Not a valid domain' => ['www+example+com'],
    'Wrong IP address' => ['18.25.36.999'],
    'Localhost IPv4 address' => ['127.0.0.1'],
    'Localhost IPv6 address' => ['::1'],
    'Wrong CIDR notation' => ['1.2.3.4/36'],
]);

test('cannot delete an unknown asset id', function () {
    asTenant1User();

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

test('cannot delete monitored asset', function ($assetAddress, $tld, $type) {
    asTenant1User();

    $asset = createAsset($assetAddress, true);

    // Delete
    $this
        ->setRpcRoute('v2.private.rpc.endpoint')
        ->callProcedure('assets@delete', [
            'asset_id' => $asset->id,
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
    'Valid IPv4' => ['93.184.215.14', null, 'IP'],
    'Valid IPv6' => ['2001:bc8:701:1b:b283:feff:fed3:ebf1', null, 'IP'],
    'Valid IPv4 CIDR' => ['157.54.9.25/28', null, 'RANGE'],
    'Valid IPv6 CIDR' => ['2001:bc8:701:1b:b283:feff:fed3:ebf1/122', null, 'RANGE'],
]);
