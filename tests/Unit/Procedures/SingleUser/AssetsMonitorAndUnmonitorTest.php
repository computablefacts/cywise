<?php


uses(\Sajya\Server\Testing\ProceduralRequests::class);
uses(\Tests\AssetsProcedureHelpers::class);

test('the monitoring begins', function ($asset) {
    $this->actingAs($this->userTenant1);

    $assetId = $this->createAsset($asset, false);

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
    'Valid IP' => ['93.184.215.14'],
    'Valid CIDR' => ['255.255.255.255/32'],
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

    $assetId = $this->createAsset($asset, true);

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
    'Valid IP' => ['93.184.215.14'],
    'Valid CIDR' => ['255.255.255.255/32'],
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