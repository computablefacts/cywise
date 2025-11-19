<?php

use App\Models\Asset;
use App\Models\AssetTag;
use App\Models\Port;
use App\Models\Scan;
use Illuminate\Support\Carbon;

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

function createScan(?Asset $asset = null, $data = []): Scan
{
    if (! $asset) {
        $asset = createAsset($data['assetAddress'] ?? 'www.example.com', true);
    }

    $scan = Scan::query()->create([
        'asset_id' => $asset->id,
        'ports_scan_id' => $data['task_id'] ?? '6409ae68ed42e11e31e5f19d',
        'ports_scan_begins_at' => $data['ports_scan_begins_at'] ?? Carbon::now(),
        'ports_scan_ends_at' => null,
        'vulns_scan_id' => null,
        'vulns_scan_begins_at' => null,
        'vulns_scan_ends_at' => null,
    ]);
    $asset->next_scan_id = $scan->ports_scan_id;
    $asset->save();

    return $scan;
}

function createPort(?Scan $scan = null, $data = []): Port
{
    if (! $scan) {
        $scan = createScan(null, $data);
    }
    $scan->ports_scan_ends_at = $data['ports_scan_ends_at'] ?? Carbon::now();
    $scan->save();

    return Port::query()->create([
        'scan_id' => $scan->id,
        'hostname' => $data['assetAddress'] ?? 'www.example.com',
        'ip' => $data['ip'] ?? '93.184.215.14',
        'port' => $data['port'] ?? 80,
        'protocol' => $data['protocol'] ?? 'tcp',
    ]);
}
