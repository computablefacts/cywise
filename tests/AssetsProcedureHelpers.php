<?php

namespace Tests;

trait AssetsProcedureHelpers
{
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

}
