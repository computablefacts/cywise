<?php

uses(\Sajya\Server\Testing\ProceduralRequests::class);

test('the monitoring begins', function ($asset) {
    $this->actingAs($this->userTenant1);

    $assetId = createAsset($asset, false);

    $this
        ->setRpcRoute('v2.private.rpc.endpoint')
        ->callProcedure('assets@monitor', [
            'asset_id' => $assetId,
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
            'status' => 'valid',
        ]);

    // Vérifier que l'actif a bien été mis à jour dans la base de données
    $this->assertDatabaseHas('am_assets', [
        'asset' => $asset,
        'is_monitored' => true,
    ]);
})->with([
    'Valid DNS' => ['www.example.com'],
    'Valid IPv4' => ['93.184.215.14'],
    'Valid IPv6' => ['2001:bc8:701:1b:b283:feff:fed3:ebf1'],
    'Valid IPv4 CIDR' => ['157.54.9.25/28', null, 'RANGE'],
    'Valid IPv6 CIDR' => ['2001:bc8:701:1b:b283:feff:fed3:ebf1/122', null, 'RANGE'],
]);

test('cannot start monitoring for an unknown asset id', function () {
    $this->actingAs($this->userTenant1);

    $this
        ->setRpcRoute('v2.private.rpc.endpoint')
        ->callProcedure('assets@monitor', [
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

test('the monitoring stops', function ($asset) {
    $this->actingAs($this->userTenant1);

    $assetId = createAsset($asset, true);

    $this
        ->setRpcRoute('v2.private.rpc.endpoint')
        ->callProcedure('assets@unmonitor', [
            'asset_id' => $assetId,
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
        ]);

    // Vérifier que l'actif a bien été mis à jour dans la base de données
    $this->assertDatabaseHas('am_assets', [
        'asset' => $asset,
        'is_monitored' => false,
    ]);
})->with([
    'Valid DNS' => ['www.example.com'],
    'Valid IPv4' => ['93.184.215.14'],
    'Valid IPv6' => ['2001:bc8:701:1b:b283:feff:fed3:ebf1'],
    'Valid IPv4 CIDR' => ['157.54.9.25/28', null, 'RANGE'],
    'Valid IPv6 CIDR' => ['2001:bc8:701:1b:b283:feff:fed3:ebf1/122', null, 'RANGE'],
]);

test('cannot unmonitor an unknown asset id', function () {
    $this->actingAs($this->userTenant1);

    $this
        ->setRpcRoute('v2.private.rpc.endpoint')
        ->callProcedure('assets@unmonitor', [
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
