<?php

namespace App\Http\Procedures;

use App\Http\Requests\JsonRpcRequest;
use App\Models\Alert;
use App\Models\Asset;
use App\Models\HiddenAlert;
use Sajya\Server\Procedure;

class VulnerabilitiesProcedure extends Procedure
{
    public static string $name = 'vulnerabilities';

    #[RpcMethod(
        description: "Compute the number of high, medium and low vulnerabilities for a given user.",
        params: [],
        result: [
            "high" => "The number of vulnerabilities with criticality high.",
            "medium" => "The number of vulnerabilities with criticality medium.",
            "low" => "The number of vulnerabilities with criticality low.",
        ],
    )]
    public function counts(JsonRpcRequest $request): array
    {
        $assets = Asset::query()->where('is_monitored', true)->get();
        return [
            'high' => $assets->map(fn(Asset $asset) => $asset->alertsWithCriticalityHigh()->count())->sum(),
            'medium' => $assets->map(fn(Asset $asset) => $asset->alertsWithCriticalityMedium()->count())->sum(),
            'low' => $assets->map(fn(Asset $asset) => $asset->alertsWithCriticalityLow()->count())->sum(),
        ];
    }

    #[RpcMethod(
        description: "List the user's vulnerabilities.",
        params: [
            "asset_id" => "An optional asset id.",
            "asset" => "An optional asset as a domain or an IP address. (string|nullable|min:1|max:191|exists:am_assets,asset)",
            "level" => "An optional criticality level such as high, medium or low. (string|nullable|min:3|max:6|in:high,medium,low)",
            "tld" => "An optional asset TLD to match. (string|nullable)",
            "tags" => "An optional list of assets tags to match.",
            "port_tags" => "An optional list of ports tags to match.",
        ],
        result: [
            "high" => "A list of vulnerabilities with critical severity.",
            "medium" => "A list of vulnerabilities with medium severity.",
            "low" => "A list of vulnerabilities with low severity.",
        ],
        ai_examples: [
            "if the request is 'quelles sont mes vulnérabilités ?', the input should be '{}'",
            "if the request is 'quelles sont mes vulnérabilités critiques ?', the input should be '{\"level\":\"high\"}'",
            "if the request is 'quelles sont les vulnérabilités de example.com ?', the input should be '{\"tld\":\"example.com\"}'",
            "if the request is 'quelles sont les vulnérabilités de www.example.com ?', the input should be '{\"asset\":\"www.example.com\"}'",
            "if the request is 'quelles sont les vulnérabilités de criticité basse de blog.example.com ?', the input should be '{\"asset\":\"blog.example.com\",\"level\":\"low\"}'",
            "if the request is 'quelles sont les vulnérabilités de criticité moyenne du serveur 192.168.1.1 ?', the input should be '{\"asset\":\"192.168.1.1\",\"level\":\"medium\"}'",
        ],
        ai_result: "
@foreach(\$result as \$key => \$value)
@if(!empty(\$value))
@php
\$alerts = collect(\$value ?? [])->map(fn(array \$event) => (new \App\Models\Alert())->forceFill(\$event));
@endphp
# Vulnerabilities of {{ \$key }} severity
@foreach(\$alerts as \$alert)
@php
if (empty(\$alert->cve_id)) {
   \$cve = '';
} else {
   \$cve = '**Note.** Cette vulnérabilité a pour identifiant [' . \$alert->cve_id . '](https://nvd.nist.gov/vuln/detail/' . \$alert->cve_id . ').';
}
\$vulnerability = \$alert->translated('vulnerability');
\$remediation = \$alert->translated('remediation');
@endphp
## {{ \$alert->title }}

**Actif concerné.** L'actif concerné est {{ \$alert->asset()?->asset }} pointant vers le serveur {{ \$alert->port?->ip }}. Le port {{ \$alert->port?->port }} de ce serveur est ouvert et expose un service {{ \$alert->port?->service }} ({{ \$alert->port?->product }}).

**Description détaillée.** {{ \$vulnerability }}

**Remédiation.** {{ \$remediation }}

{{ \$cve }}

@endforeach
@endif
@endforeach
        ",
    )]
    public function list(JsonRpcRequest $request): array
    {
        $params = $request->validate([
            'asset_id' => 'integer|nullable|prohibited_if:asset,true|exists:am_assets,id',
            'asset' => 'string|nullable|prohibited_if:asset_id,true|min:1|max:191|exists:am_assets,asset',
            'level' => 'string|nullable|min:3|max:6|in:high,medium,low',
            'tld' => 'string|nullable',
            'tags' => 'array|nullable|min:1|max:10',
            'tags.*' => 'string',
            'port_tags' => 'array|nullable|min:1|max:10',
            'port_tags.*' => 'string',
        ]);

        $assetId = $params['asset_id'] ?? null;
        $tld = $params['tld'] ?? null;
        $tags = $params['tags'] ?? null;
        $portTags = $params['port_tags'] ?? null;
        $alerts = Asset::query()
            ->where('is_monitored', true)
            ->when($assetId, fn($query, $assetId) => $query->where('id', $assetId))
            ->when($tld, fn($query, $domain) => $query->where('tld', $tld))
            ->when($tags, fn($query, $domain) => $query
                ->join('am_assets_tags', 'am_assets_tags.asset_id', '=', 'am_assets.id')
                ->whereIn('am_assets_tags.tag', $tags)
            )
            ->get()
            ->flatMap(function (Asset $asset) use ($params, $portTags) {
                if (($params['level'] ?? '') === 'high') {
                    $query = $asset->alertsWithCriticalityHigh();
                } else if (($params['level'] ?? '') === 'medium') {
                    $query = $asset->alertsWithCriticalityMedium();
                } else if (($params['level'] ?? '') === 'low') {
                    $query = $asset->alertsWithCriticalityLow();
                } else {
                    $query = $asset->alerts();
                }
                if ($portTags) {
                    $query->join('am_ports_tags', 'am_ports_tags.port_id', '=', 'alerts_dedup.port_id')
                        ->whereIn('am_ports_tags.tag', $portTags);
                }
                return $query->distinct()->get();
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