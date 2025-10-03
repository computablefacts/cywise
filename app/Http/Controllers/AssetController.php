<?php

namespace App\Http\Controllers;

use App\Helpers\VulnerabilityScannerApiUtilsFacade as ApiUtils;
use App\Http\Procedures\AssetsProcedure;
use App\Http\Requests\JsonRpcRequest;
use App\Models\Asset;
use App\Models\AssetTag;
use App\Models\Screenshot;
use App\Rules\IsValidIpAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/** @deprecated */
class AssetController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function discover(Request $request): array
    {
        return (new AssetsProcedure())->discover(JsonRpcRequest::createFrom($request));
    }

    public function discoverFromIp(Request $request): array
    {
        $ip = trim($request->string('ip', ''));

        if (!IsValidIpAddress::test($ip)) {
            abort(500, "Invalid IP address : {$ip}");
        }
        return ApiUtils::discover_from_ip_public($ip);
    }

    public function saveAsset(Request $request): array
    {
        return (new AssetsProcedure())->create(JsonRpcRequest::createFrom($request));
    }

    public function userAssets(Request $request): array
    {
        $request->merge([
            'is_monitored' => $request->string('valid'),
            'created_the_last_x_hours' => $request->integer('hours'),
        ]);
        return (new AssetsProcedure())->list(JsonRpcRequest::createFrom($request));
    }

    public function screenshot(Screenshot $screenshot): array
    {
        return [
            "screenshot" => $screenshot->png,
        ];
    }

    public function addTag(Asset $asset, Request $request): Collection
    {
        $request->merge([
            'asset_id' => $asset->id,
            'tag' => $request->input('key', ''),
        ]);
        $tag = (new AssetsProcedure())->tag(JsonRpcRequest::createFrom($request));
        return collect([[
            'id' => $tag['tag']->id,
            'key' => $tag['tag']->tag,
        ]]);
    }

    public function removeTag(Asset $asset, AssetTag $assetTag): void
    {
        $request = new Request([
            'asset_id' => $asset->id,
            'tag_id' => $assetTag->id,
        ]);
        $request->setUserResolver(fn() => auth()->user());
        (new AssetsProcedure())->untag(JsonRpcRequest::createFrom($request));
    }

    public function infosFromAsset(string $assetBase64, int $trialId = 0): array
    {
        $request = new Request([
            'asset' => base64_decode($assetBase64),
            'trial_id' => $trialId,
        ]);
        $request->setUserResolver(fn() => auth()->user());
        return (new AssetsProcedure())->get(JsonRpcRequest::createFrom($request));
    }
}