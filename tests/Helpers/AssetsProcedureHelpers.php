<?php

use App\Models\Asset;
use App\Models\AssetTag;

function createAsset(string $assetAddress, bool $watch = false): Asset
{
    $response = test()
        ->setRpcRoute('v2.private.rpc.endpoint')
        ->callProcedure('assets@create', [
            'asset' => $assetAddress
        ,
            'watch' => $watch,
        ]);

    return Asset::query()->find($response->json('result.asset.uid'));
}

function createTag(Asset $asset, string $tagLabel): AssetTag
{
    $response = test()
        ->setRpcRoute('v2.private.rpc.endpoint')
        ->callProcedure('assets@tag', [
            'asset_id' => $asset->id,
            'tag' => $tagLabel,
        ]);

    return AssetTag::query()->find($response->json('result.tag.id'));
}
