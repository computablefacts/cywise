<?php

namespace Tests\Unit;

use Tests\TestCase;
use Sajya\Server\Testing\ProceduralRequests;

use App\Helpers\VulnerabilityScannerApiUtilsFacade as ApiUtils;
use App\Models\Asset;
use PHPUnit\Framework\Attributes\TestWith;

class PatrickTest extends TestCase
{
    use ProceduralRequests;

    public function createAsset($asset, $watch = false): int
    {
        $response = $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('assets@create', [
                'asset' => $asset,
                'watch' => $watch,
            ]);

        return $response->json('result.asset.uid');
    }

    public function createTag($assetId, $tag): int
    {
        $response = $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('assets@tag', [
                'asset_id' => $assetId,
                'tag' => $tag,
            ]);

        return $response->json('result.tag.id');
    }

    public function testAssetsDiscover(): void
    {
        ApiUtils::shouldReceive('discover_public')
            ->once()
            ->with('example.com')
            ->andReturn([
                'subdomains' => ['www1.example.com', 'www1.example.com' /* duplicate! */, 'www2.example.com'],
            ]);

        $this->actingAs($this->userTenant1);

        $this
            ->setRpcRoute('v2.private.rpc.endpoint')
            ->callProcedure('assets@discover', [
                'domain' => 'example.com'
            ])
            ->assertJsonFragment([
                'result' => [
                    'subdomains' => ['www1.example.com', 'www1.example.com', 'www2.example.com'],
                ],
            ]);
    }

    #[TestWith(['www.example.com', 'example.com', 'DNS'], 'Valid DNS')]
    #[TestWith(['93.184.215.14', null, 'IP'], 'Valid IP')]
    #[TestWith(['255.255.255.255/32', null, 'RANGE'], 'Valid CIDR')]
    public function testCreateAndDeleteValidAsset($asset, $tld, $type): void
    {
        $this->actingAs($this->userTenant1);

        // Create
        $this
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
        $assets = Asset::where('asset', $asset)->get();
        $this->assertEquals(1, $assets->count());

        $assetId = $assets->first()->id;

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
    }

    #[TestWith(['invalid_asset'], 'Just a string')]
    #[TestWith(['www+example+com'], 'Not a valid domain')]
    #[TestWith(['18.25.36.999'], 'Wrong IP address')]
// TODO:    #[TestWith(['127.0.0.1'], 'Localhost IP address')]
    #[TestWith(['1.2.3.4/36'], 'Wrong CIDR notation')]
    public function testInvalidAssetsAreNotAdded($asset)
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

    #[TestWith(['www.example.com'], 'Valid DNS')]
    #[TestWith(['93.184.215.14'], 'Valid IP')]
    #[TestWith(['255.255.255.255/32'], 'Valid CIDR')]
    public function testTheMonitoringBegins($asset): void
    {
        $this->actingAs($this->userTenant1);

        $assetId = $this->createAsset($asset, true);

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

    #[TestWith(['www.example.com'], 'Valid DNS')]
    #[TestWith(['93.184.215.14'], 'Valid IP')]
    #[TestWith(['255.255.255.255/32'], 'Valid CIDR')]
    public function testCreateAnAssetAndAddATag($asset): void
    {
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
    }

    #[TestWith(['www.example.com'], 'Valid DNS')]
    #[TestWith(['93.184.215.14'], 'Valid IP')]
    #[TestWith(['255.255.255.255/32'], 'Valid CIDR')]
    public function testRemoveTag($asset): void
    {
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
    }

    public function testCannotRemoveANonExistentTag(): void
    {
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
    }

    public function testListTags(): void
    {
        $this->actingAs($this->userTenant1);
        $assetId = $this->createAsset('www.example.com');
        $this->createTag($assetId, 'tag1');
        $this->createTag($assetId, 'tag2');

        $this->actingAs($this->userTenant2);
        $assetId = $this->createAsset('www.example2.com');
        $this->createTag($assetId, 'tag3');
        $this->createTag($assetId, 'tag4');
        $this->createTag($assetId, 'tag5');

        $this->actingAs($this->userTenant1);
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

        $this->actingAs($this->userTenant2);
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
    }

}
