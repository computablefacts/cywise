<?php

namespace Tests\Unit\Procedures\MultiUsers;

use Tests\TestCase;
use Sajya\Server\Testing\ProceduralRequests;

use Tests\AssetsProcedureHelpers;

class AssetsTagsTest extends TestCase
{
    use ProceduralRequests;
    use AssetsProcedureHelpers;

    public function testListTagsDependsOnUser(): void
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
