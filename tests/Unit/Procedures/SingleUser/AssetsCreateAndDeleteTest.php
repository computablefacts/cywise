<?php

namespace Tests\Unit\Procedures\SingleUser;

use Tests\TestCaseWithDb;
use Sajya\Server\Testing\ProceduralRequests;

use App\Models\Asset;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\AssetsProcedureHelpers;

class AssetsCreateAndDeleteTest extends TestCaseWithDb
{
    use ProceduralRequests;
    use AssetsProcedureHelpers;

    #[TestWith(['www.example.com', 'example.com', 'DNS'], 'Valid DNS')]
    #[TestWith(['93.184.215.14', null, 'IP'], 'Valid IP')]
    #[TestWith(['255.255.255.255/32', null, 'RANGE'], 'Valid CIDR')]
    public function testCreateAndDeleteValidAsset($asset, $tld, $type): void
    {
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
    }

    #[TestWith(['invalid_asset'], 'Just a string')]
    #[TestWith(['www+example+com'], 'Not a valid domain')]
    #[TestWith(['18.25.36.999'], 'Wrong IP address')]
// TODO:    #[TestWith(['127.0.0.1'], 'Localhost IP address')]
    #[TestWith(['1.2.3.4/36'], 'Wrong CIDR notation')]
    public function testInvalidAssetsAreNotCreated($asset)
    {
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
    }

    public function testCannotDeleteAnUnknownAssetId(): void
    {
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
    }

    #[TestWith(['www.example.com', 'example.com', 'DNS'], 'Valid DNS')]
    #[TestWith(['93.184.215.14', null, 'IP'], 'Valid IP')]
    #[TestWith(['255.255.255.255/32', null, 'RANGE'], 'Valid CIDR')]
    public function testCannotDeleteMonitoredAsset($asset, $tld, $type): void
    {
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
    }

}
