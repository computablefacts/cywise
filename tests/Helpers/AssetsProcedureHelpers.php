<?php

function createAsset($asset, $watch = false): int
{
    $response = test()
        ->setRpcRoute('v2.private.rpc.endpoint')
        ->callProcedure('assets@create', [
            'asset' => $asset,
            'watch' => $watch,
        ]);

    return $response->json('result.asset.uid');
}

function createTag($assetId, $tag): int
{
    $response = test()
        ->setRpcRoute('v2.private.rpc.endpoint')
        ->callProcedure('assets@tag', [
            'asset_id' => $assetId,
            'tag' => $tag,
        ]);

    return $response->json('result.tag.id');
}
