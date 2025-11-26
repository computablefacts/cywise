<?php

namespace App\Http\Procedures;

use App\Http\Requests\JsonRpcRequest;
use App\Models\Alert;
use App\Models\Asset;
use App\Models\HiddenAlert;
use Sajya\Server\Attributes\RpcMethod;
use Sajya\Server\Procedure;

class VulnerabilitiesProcedure extends Procedure
{
    public static string $name = 'vulnerabilities';

    #[RpcMethod(
        description: "List the user's vulnerabilities.",
        params: [
            "asset_id" => "The asset id (optional).",
            "level" => "The vulnerabilities criticality level (optional).",
            "tld" => "The underlying asset TLD to match (optional).",
            "tags" => "The underlying list of assets tags to match (optional).",
        ],
        result: [
            "high" => "A list of vulnerabilities with critical severity.",
            "medium" => "A list of vulnerabilities with medium severity.",
            "low" => "A list of vulnerabilities with low severity.",
        ]
    )]
    public function list(JsonRpcRequest $request): array
    {
        $params = $request->validate([
            'asset_id' => 'nullable|integer|exists:am_assets,id',
            'level' => 'nullable|string|min:3|max:6|in:high,medium,low',
            'tld' => 'nullable|string',
            'tags' => 'nullable|array|min:1|max:10',
            'tags.*' => 'string',
        ]);

        $assetId = $params['asset_id'] ?? null;
        $tld = $params['tld'] ?? null;
        $tags = $params['tags'] ?? null;
        $alerts = Asset::where('is_monitored', true)
            ->when($assetId, fn($query, $assetId) => $query->where('id', $assetId))
            ->when($tld, fn($query, $domain) => $query->where('tld', $tld))
            ->when($tags, fn($query, $domain) => $query
                ->join('am_assets_tags', 'am_assets_tags.asset_id', '=', 'am_assets.id')
                ->whereIn('am_assets_tags.tag', $tags)
            )
            ->get()
            ->flatMap(function (Asset $asset) use ($params) {
                if (($params['level'] ?? '') === 'high') {
                    $query = $asset->alertsWithCriticalityHigh();
                } else if (($params['level'] ?? '') === 'medium') {
                    $query = $asset->alertsWithCriticalityMedium();
                } else if (($params['level'] ?? '') === 'low') {
                    $query = $asset->alertsWithCriticalityLow();
                } else {
                    $query = $asset->alerts();
                }
                return $query->get();
            })
            ->filter(fn(Alert $alert) => $alert->is_hidden === 0);

        return [
            'high' => $alerts->filter(fn(Alert $alert) => $alert->isHigh())->values(),
            'medium' => $alerts->filter(fn(Alert $alert) => $alert->isMedium())->values(),
            'low' => $alerts->filter(fn(Alert $alert) => $alert->isLow())->values(),
        ];
    }

    #[RpcMethod(
        description: "Hide/Show one or more vulnerabilities.",
        params: [
            'uid' => 'The vulnerability unique identifier (optional).',
            'type' => 'The vulnerability type (optional).',
            'title' => 'The vulnerability title (optional).',
        ],
        result: [
            "msg" => "A success message.",
        ]
    )]
    public function toggleVisibility(JsonRpcRequest $request): array
    {
        $params = $request->validate([
            'uid' => 'nullable|string',
            'type' => 'nullable|string',
            'title' => 'nullable|string',
        ]);

        $uid = trim($params['uid'] ?? '');
        $type = trim($params['type'] ?? '');
        $title = trim($params['title'] ?? '');

        if (empty($uid) && empty($type) && empty($title)) {
            throw new \Exception('At least one of uid, type or title must be present.');
        }

        $query = HiddenAlert::query();

        if (!empty($uid)) {
            $query->where('uid', $uid);
        } else if (!empty($type)) {
            $query->where('type', $type);
        } else if (!empty($title)) {
            $query->where('title', $title);
        }

        /** @var HiddenAlert $marker */
        $marker = $query->first();

        if ($marker) {
            $marker->delete();
            $isVisible = true;
        } else {
            $marker = HiddenAlert::create([
                'uid' => $uid,
                'type' => $type,
                'title' => $title,
            ]);
            $isVisible = false;
        }
        return [
            "msg" => $isVisible ?
                "Your alerts will be visible from now on!" :
                "Your alerts will be hidden from now on!",
        ];
    }

    #[RpcMethod(
        description: "Flag a given vulnerability as resolved and trigger a new scan.",
        params: [
            'vulnerability_id' => 'The vulnerability id.',
        ],
        result: [
            "msg" => "A success message.",
        ]
    )]
    public function markAsResolved(JsonRpcRequest $request): array
    {
        $params = $request->validate([
            'vulnerability_id' => 'required|integer|exists:am_alerts,id',
        ]);

        /** @var Alert $alert */
        $alert = Alert::find($params['vulnerability_id']);
        $request = $request->replace(['asset_id' => $alert->asset()->id]);
        (new AssetsProcedure())->restartScan($request);

        return [
            'msg' => "The vulnerability has been marked as resolved and will be re-scanned soon.",
        ];
    }
}