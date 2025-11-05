<?php

namespace Tests\Unit\Procedures\SingleUser;

use Tests\TestCaseWithDb;
use Sajya\Server\Testing\ProceduralRequests;

use PHPUnit\Framework\Attributes\TestWith;
use Tests\AssetsProcedureHelpers;

class AssetsMonitorAndUnmonitorTest extends TestCaseWithDb
{
    use ProceduralRequests;
    use AssetsProcedureHelpers;

    #[TestWith(['www.example.com'], 'Valid DNS')]
    #[TestWith(['93.184.215.14'], 'Valid IP')]
    #[TestWith(['255.255.255.255/32'], 'Valid CIDR')]
    public function testTheMonitoringBegins($asset): void
    {
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
    }

    public function testCannotStartMonitoringForAnUnknownAssetId(): void
    {
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
    }

    #[TestWith(['www.example.com'], 'Valid DNS')]
    #[TestWith(['93.184.215.14'], 'Valid IP')]
    #[TestWith(['255.255.255.255/32'], 'Valid CIDR')]
    public function testTheMonitoringStops($asset): void
    {
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
    }

    public function testCannotUnmonitorAnUnknownAssetId(): void
    {
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
    }

}
