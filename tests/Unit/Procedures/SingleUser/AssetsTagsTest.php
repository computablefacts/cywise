<?php

namespace Tests\Unit\Procedures\SingleUser;

use Tests\TestCase;
use Sajya\Server\Testing\ProceduralRequests;

use PHPUnit\Framework\Attributes\TestWith;
use Tests\AssetsProcedureHelpers;

class AssetsTagsTest extends TestCase
{
    use ProceduralRequests;
    use AssetsProcedureHelpers;

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

}
