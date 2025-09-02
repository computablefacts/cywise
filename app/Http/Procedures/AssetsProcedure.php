<?php

namespace App\Http\Procedures;

use App\Events\BeginPortsScan;
use App\Helpers\VulnerabilityScannerApiUtilsFacade as ApiUtils;
use App\Listeners\CreateAssetListener;
use App\Listeners\DeleteAssetListener;
use App\Mail\HoneypotRequested;
use App\Mail\MailCoachSimpleEmail;
use App\Models\Alert;
use App\Models\Asset;
use App\Models\AssetTag;
use App\Models\AssetTagHash;
use App\Models\HiddenAlert;
use App\Models\Port;
use App\Models\PortTag;
use App\Models\Scan;
use App\Models\User;
use App\Rules\IsValidAsset;
use App\Rules\IsValidDomain;
use App\Rules\IsValidIpAddress;
use App\Rules\IsValidTag;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Sajya\Server\Attributes\RpcMethod;
use Sajya\Server\Procedure;

class AssetsProcedure extends Procedure
{
    private const BLACKLIST = [
        "amazonaws.com",
        "microsoft.com",
        "azure.net",
        "wordpress.com",
        "google.com",
        "co.uk",
        "co.jp",
        "com.au"
    ];

    public static string $name = 'assets';

    #[RpcMethod(
        description: "Discover subdomains of a given domain.",
        params: [
            "domain" => "The seed domain.",
        ],
        result: [
            "subdomains" => "An array of subdomains.",
        ]
    )]
    public function discover(Request $request): array
    {
        $params = $request->validate([
            'domain' => 'required|string|min:1|max:191',
        ]);

        $domain = trim($params['domain']);

        if (!IsValidDomain::test($domain)) {
            return [];
        }
        if (in_array($domain, self::BLACKLIST)) {
            throw new \Exception("This domain is blacklisted : {$domain}");
        }
        $response = ApiUtils::discover_public($domain);
        try {
            if (($response['fallback'] ?? false) === true) {
                $subject = "no subdomain for {$domain}";
                $body = [
                    "domain" => $domain,
                ];
                MailCoachSimpleEmail::sendEmail("Cywise : No subdomain for {$domain}", "No subdomain for {$domain}", "Please, investigate.");
                Mail::to(config('towerify.freshdesk.to_email'))->send(new HoneypotRequested(config('towerify.freshdesk.from_email'), 'Support', $subject, $body));
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
        return $response;
    }

    #[RpcMethod(
        description: "Get everything known about a single asset.",
        params: [
            "asset" => "The asset name.",
            "trial_id" => "The trial id this asset belongs to (if any).",
        ],
        result: [
            "asset" => "The asset name. May be different from the one given in the request on ranges.",
            'modifications' => "The asset's creation and modification history.",
            'tags' => "The asset's tags.",
            'ports' => "The asset's open ports.",
            'vulnerabilities' => "The asset's vulnerabilities.",
            'timeline' => [
                'nmap' => [
                    'start' => "When the port scan started.",
                    'end' => "When the port scan ended.",
                ],
                'sentinel' => [
                    'start' => "When the vuln. scan started.",
                    'end' => "When the vuln. scan ended.",
                ],
                'next_scan' => "When the next scans will start.",
                'nb_vulns_scans_running' => "The number of running scans.",
                'nb_vulns_scans_completed' => "The number of completed scans.",
            ],
            'hiddenAlerts' => "The asset's hidden vulnerabilities (if any).",
        ]
    )]
    public function get(Request $request): array
    {
        if (!$request->user()->canUseAdversaryMeter()) {
            throw new \Exception('Missing permission.');
        }

        $params = $request->validate([
            'asset' => 'required|string|min:1|max:191',
            'trial_id' => 'integer|min:0',
        ]);

        $domainOrIpOrRange = $params['asset'];
        $trialId = $params['trial_id'] ?? 0;

        if ($trialId > 0) {
            /** @var Asset $asset */
            $asset = Asset::where('asset', $domainOrIpOrRange)->where('ynh_trial_id', $trialId)->first();
        } else {
            /** @var Asset $asset */
            $asset = Asset::where('asset', $domainOrIpOrRange)->first();
        }
        if (!$asset) {

            // The asset cannot be identified: check if it is an IP address from a known range
            if (IsValidIpAddress::test($domainOrIpOrRange)) {

                /** @var Asset $asset */
                $asset = Asset::select('am_assets.*')
                    ->join('am_scans', 'am_scans.ports_scan_id', '=', 'am_assets.cur_scan_id')
                    ->join('am_ports', 'am_ports.scan_id', '=', 'am_scans.id')
                    ->where('am_ports.ip', $domainOrIpOrRange)
                    ->first();

                if ($asset) {
                    $request2 = new Request();
                    $request2->replace([
                        'asset' => $asset->asset,
                        'trial_id' => $trialId,
                    ]);
                    return $this->get($request2);
                }
            }
            throw new \Exception("The asset could not be identified : {$domainOrIpOrRange}");
        }

        // Load the asset's tags
        $tags = $asset->tags()->orderBy('tag')->get()->pluck('tag')->toArray();

        // Load the asset's open ports
        $ports = $asset->ports()
            ->where('closed', false)
            ->orderBy('port')
            ->get()
            ->map(function (Port $port) {
                return [
                    'ip' => $port->ip,
                    'port' => $port->port,
                    'protocol' => $port->protocol,
                    'products' => [$port->product],
                    'services' => [$port->service],
                    'tags' => $port->tags()->orderBy('tag')->get()->pluck('tag')->toArray(),
                    'screenshotId' => $port->screenshot()->first()?->id,
                ];
            })
            ->toArray();

        // Load the asset's alerts
        $alerts = $asset->alerts()
            ->get()
            ->map(function (Alert $alert) use ($asset) {

                $port = $alert->port();

                return [
                    'id' => $alert->id,
                    'ip' => $port->ip,
                    'port' => $port->port,
                    'protocol' => $port->protocol,
                    'type' => $alert->type,
                    'tested' => $alert->events()->exists(),
                    'vulnerability' => $alert->vulnerability,
                    'remediation' => $alert->remediation,
                    'level' => Str::lower($alert->level),
                    'uid' => $alert->uid,
                    'cve_id' => $alert->cve_id,
                    'cve_cvss' => $alert->cve_cvss,
                    'cve_vendor' => $alert->cve_vendor,
                    'cve_product' => $alert->cve_product,
                    'title' => $alert->title,
                    'flarum_url' => null,
                    'start_date' => $alert->created_at,
                    'is_hidden' => $alert->is_hidden === 1,
                ];
            })
            ->sortBy([
                ['ip', 'asc'],
                ['port', 'asc'],
                ['protocol', 'asc'],
            ])
            ->values();

        // Load the asset's scans
        $scansInProgress = $asset->scanInProgress();

        if ($scansInProgress->isNotEmpty()) {
            $scans = $scansInProgress;
        } else {
            $scans = $asset->scanCompleted();
        }

        $portsScanBeginsAt = $scans
            ->filter(fn(Scan $scan) => $scan->ports_scan_begins_at != null)
            ->sortBy('ports_scan_begins_at')
            ->first()?->ports_scan_begins_at;

        $portsScanEndsAt = $scans->contains(fn(Scan $scan) => $scan->ports_scan_ends_at == null) ?
            null :
            $scans->sortBy('ports_scan_ends_at')->last()?->ports_scan_ends_at;

        $vulnsScanBeginsAt = $scans
            ->filter(fn(Scan $scan) => $scan->vulns_scan_ends_at != null)
            ->sortBy('vulns_scan_begins_at')
            ->first()?->vulns_scan_begins_at;

        $vulnsScanEndsAt = $scans->contains(fn(Scan $scan) => $scan->vulns_scan_ends_at == null) ?
            null :
            $scans->sortBy('vulns_scan_ends_at')->last()?->vulns_scan_ends_at;

        $frequency = config('towerify.adversarymeter.days_between_scans');
        $nextScanDate = $asset->is_monitored ?
            $vulnsScanEndsAt ?
                Carbon::create($vulnsScanEndsAt)->addDays((int)$frequency) :
                ($vulnsScanBeginsAt ? Carbon::create($vulnsScanBeginsAt)->addDays((int)$frequency) : Carbon::now()) :
            null;

        // Load the identity of the user who created the asset
        $user = $asset->createdBy();

        return [
            'asset' => $asset->asset,
            'modifications' => [[
                'asset_id' => $asset->id,
                'asset_name' => $asset->asset,
                'timestamp' => $asset->updated_at,
                'user' => $user ? $user->email : 'unknown',
            ]],
            'tags' => $tags,
            'ports' => $ports,
            'vulnerabilities' => $alerts->toArray(),
            'timeline' => [
                'nmap' => [
                    'id' => $scans->first()?->ports_scan_id,
                    'start' => $portsScanBeginsAt,
                    'end' => $portsScanEndsAt,
                ],
                'sentinel' => [
                    'id' => $vulnsScanBeginsAt ? '000000000000000000000000' : null,
                    'start' => $vulnsScanBeginsAt,
                    'end' => $vulnsScanEndsAt,
                ],
                'next_scan' => $nextScanDate,
                'nb_vulns_scans_running' => $scans->filter(fn(Scan $scan) => $scan->vulns_scan_ends_at == null)->count(),
                'nb_vulns_scans_completed' => $scans->filter(fn(Scan $scan) => $scan->vulns_scan_ends_at != null)->count(),
            ],
            'hiddenAlerts' => HiddenAlert::all()->toArray(),
        ];
    }

    #[RpcMethod(
        description: "Create a single asset.",
        params: [
            "asset" => "The asset as an IP address or a DNS.",
            "watch" => "True if the asset should be monitored directly after the creation. False otherwise.",
            "trial_id" => "The trial id this asset belongs to (if any).",
        ],
        result: [
            "asset" => "An asset object.",
        ]
    )]
    public function create(Request $request): array
    {
        if (!$request->user()->canUseAdversaryMeter()) {
            throw new \Exception('Missing permission.');
        }

        $params = $request->validate([
            'asset' => 'required|string|min:1|max:191',
            'watch' => 'boolean',
            'trial_id' => 'integer|exists:ynh_trials,id',
        ]);

        if (!IsValidAsset::test($params['asset'])) {
            throw new \Exception("Invalid asset : {$params['asset']}");
        }

        $asset = $params['asset'];
        $watch = is_bool($params['watch']) && $params['watch'];
        $trialId = $params['trial_id'] ?? 0;
        /** @var User $user */
        $user = Auth::user();
        $obj = CreateAssetListener::execute($user, $asset, $watch, [], $trialId);

        if (!$obj) {
            throw new \Exception("The asset could not be created : {$params['asset']}");
        }
        return [
            'asset' => $this->convertAsset($obj->refresh()),
        ];
    }

    #[RpcMethod(
        description: "Delete an asset.",
        params: [
            "asset_id" => "The asset id.",
        ],
        result: [
            "msg" => "A success message.",
        ]
    )]
    public function delete(Request $request): array
    {
        if (!$request->user()->canUseAdversaryMeter()) {
            throw new \Exception('Missing permission.');
        }

        $params = $request->validate([
            'asset_id' => 'required|integer|exists:am_assets,id',
        ]);

        /** @var Asset $asset */
        $asset = Asset::find($params['asset_id']);

        if ($asset->is_monitored) {
            throw new \Exception('Deletion not allowed, asset is monitored.');
        }

        /** @var User $user */
        $user = Auth::user();
        DeleteAssetListener::execute($user, $asset->asset);

        return [
            "msg" => "{$asset->asset} has been removed.",
        ];
    }

    #[RpcMethod(
        description: "List the user's assets.",
        params: [
            "is_monitored" => "The asset status: true to get only monitored assets, false to get only unmonitored assets, null to get all assets.",
            "created_the_last_x_hours" => "Keep only assets created after (now - x hours).",
        ],
        result: [
            "assets" => "A list of assets.",
        ]
    )]
    public function list(Request $request): array
    {
        if (!$request->user()->canUseAdversaryMeter()) {
            throw new \Exception('Missing permission.');
        }

        $params = $request->validate([
            'is_monitored' => 'boolean',
            'created_the_last_x_hours' => 'integer|min:0',
        ]);

        $valid = $params['is_monitored'] ?? null;
        $hours = $params['created_the_last_x_hours'] ?? null;
        $query = Asset::query();

        if ($valid === true) {
            $query->where('is_monitored', true);
        }
        if ($valid === false) {
            $query->where('is_monitored', false);
        }
        if ($hours && is_integer($hours)) {
            $cutOffTime = now()->subHours($hours);
            $query->where('created_at', '>=', $cutOffTime);
        }

        $assets = $query->orderBy('asset')
            ->get()
            ->map(function (Asset $asset) {
                $tags = $asset->ports()
                    ->where('closed', false)
                    ->orderBy('port')
                    ->get()
                    ->flatMap(function (Port $port) use ($asset) {
                        return $port->tags()
                            ->orderBy('tag')
                            ->get()
                            ->map(function (PortTag $tag) use ($port, $asset) {
                                if ($asset->isRange()) {
                                    return [
                                        'asset' => $port->ip,
                                        'port' => $port->port,
                                        'tag' => Str::lower($tag->tag),
                                        'is_range' => true,
                                    ];
                                }
                                return [
                                    'asset' => $asset->asset,
                                    'port' => $port->port,
                                    'tag' => Str::lower($tag->tag),
                                    'is_range' => false,
                                ];
                            });
                    })
                    ->toArray();
                return array_merge($this->convertAsset($asset), [
                    'tags_from_ports' => $tags
                ]);
            })
            ->all();

        return [
            'assets' => $assets,
        ];
    }

    #[RpcMethod(
        description: "Start monitoring an asset.",
        params: [
            "asset_id" => "The asset id.",
        ],
        result: [
            "asset" => "The monitored asset.",
        ]
    )]
    public function monitor(Request $request): array
    {
        if (!$request->user()->canUseAdversaryMeter()) {
            throw new \Exception('Missing permission.');
        }

        $params = $request->validate([
            'asset_id' => 'required|integer|exists:am_assets,id',
        ]);

        /** @var Asset $asset */
        $asset = Asset::find($params['asset_id']);

        if (!$asset->is_monitored) {
            $asset->is_monitored = true;
            $asset->save();
        }
        return [
            'asset' => $this->convertAsset($asset),
        ];
    }

    #[RpcMethod(
        description: "Stop monitoring an asset.",
        params: [
            "asset_id" => "The asset id.",
        ],
        result: [
            "asset" => "The unmonitored asset.",
        ]
    )]
    public function unmonitor(Request $request): array
    {
        if (!$request->user()->canUseAdversaryMeter()) {
            throw new \Exception('Missing permission.');
        }

        $params = $request->validate([
            'asset_id' => 'required|integer|exists:am_assets,id',
        ]);

        /** @var Asset $asset */
        $asset = Asset::find($params['asset_id']);

        if ($asset->is_monitored) {

            Scan::query()->where('asset_id', $asset->id)->delete();

            $asset->is_monitored = false;
            $asset->save();
        }
        return [
            'asset' => $this->convertAsset($asset),
        ];
    }

    #[RpcMethod(
        description: "Tag an asset.",
        params: [
            "asset_id" => "The asset id.",
            "tag" => "The tag to add.",
        ],
        result: [
            "tag" => "The added tag.",
        ]
    )]
    public function tag(Request $request): array
    {
        if (!$request->user()->canUseAdversaryMeter()) {
            throw new \Exception('Missing permission.');
        }

        $params = $request->validate([
            'asset_id' => 'required|integer|exists:am_assets,id',
            'tag' => 'required|string|min:1|max:191',
        ]);

        $tag = Str::lower($params['tag']);

        if (!IsValidTag::test($tag)) {
            throw new \Exception("Invalid tag : {$tag}");
        }

        /** @var Asset $asset */
        $asset = Asset::find($params['asset_id']);
        $obj = $asset->tags()->where('tag', $tag)->first();

        if (!$obj) {
            /** @var AssetTag $obj */
            $obj = $asset->tags()->create(['tag' => $tag]);
            if (!$obj) {
                throw new \Exception("The tag could not be created : {$tag}");
            }
        }
        return [
            'tag' => $obj,
        ];
    }

    #[RpcMethod(
        description: "Untag an asset.",
        params: [
            "asset_id" => "The asset id.",
            "tag_id" => "The tag id of the tag to remove.",
        ],
        result: [
            "msg" => "A success message.",
        ]
    )]
    public function untag(Request $request): array
    {
        if (!$request->user()->canUseAdversaryMeter()) {
            throw new \Exception('Missing permission.');
        }

        $params = $request->validate([
            'asset_id' => 'required|integer|exists:am_assets,id',
            'tag_id' => 'required|integer|exists:am_assets_tags,id',
        ]);

        /** @var Asset $asset */
        $asset = Asset::find($params['asset_id']);
        /** @var AssetTag $tag */
        $tag = AssetTag::find($params['tag_id']);

        if ($asset->id === $tag->asset_id) {
            $tag->delete();
        }
        return [
            "msg" => "The tag {$tag->tag} has been removed.",
        ];
    }

    #[RpcMethod(
        description: "List all tags that belong to the current user.",
        params: [],
        result: [
            "tags" => "The list of tags.",
        ]
    )]
    public function listTags(Request $request): array
    {
        if (!$request->user()->canUseAdversaryMeter()) {
            throw new \Exception('Missing permission.');
        }
        return [
            'tags' => AssetTag::query()
                ->orderBy('tag')
                ->get()
                ->pluck('tag')
                ->unique()
                ->values()
                ->toArray(),
        ];
    }

    #[RpcMethod(
        description: "Force-scan a given asset.",
        params: [
            "asset_id" => "The asset id.",
        ],
        result: [
            "asset" => "The scanned asset.",
        ]
    )]
    public function restartScan(Request $request): array
    {
        if (!$request->user()->canUseAdversaryMeter()) {
            throw new \Exception('Missing permission.');
        }

        $params = $request->validate([
            'asset_id' => 'required|integer|exists:am_assets,id',
        ]);

        /** @var Asset $asset */
        $asset = Asset::find($params['asset_id']);

        if (!$asset->is_monitored) {
            throw new \Exception("Restart scan not allowed, {$asset->asset} is not monitored.");
        }
        if ($asset->scanInProgress()->isEmpty()) {
            BeginPortsScan::dispatch($asset);
        }
        return [
            'asset' => $this->convertAsset($asset),
        ];
    }

    #[RpcMethod(
        description: "Group together assets sharing a given tag.",
        params: [
            "tag" => "The tag.",
        ],
        result: [
            "group" => "The group object.",
        ]
    )]
    public function group(Request $request): array
    {
        if (!$request->user()->canUseAdversaryMeter()) {
            throw new \Exception('Missing permission.');
        }

        $params = $request->validate([
            'tag' => 'required|string|exists:am_assets_tags,tag',
        ]);
        return [
            'group' => AssetTagHash::create([
                'tag' => $params['tag'],
                'hash' => Str::random(32),
            ]),
        ];
    }

    #[RpcMethod(
        description: "Degroup previously grouped assets.",
        params: [
            "group" => "The group hash.",
        ],
        result: [
            "msg" => "A success message.",
        ]
    )]
    public function degroup(Request $request): array
    {
        if (!$request->user()->canUseAdversaryMeter()) {
            throw new \Exception('Missing permission.');
        }

        $params = $request->validate([
            'group' => 'required|string|exists:am_assets_tags_hashes,hash',
        ]);

        /** @var AssetTagHash $group */
        $group = AssetTagHash::query()->where('hash', '=', $params['group'])->firstOrFail();
        $group->delete();

        return [
            'msg' => "The group {$params['group']} has been disbanded!",
        ];
    }

    #[RpcMethod(
        description: "List all groups that belong to the current user.",
        params: [],
        result: [
            "groups" => "The list of groups.",
        ]
    )]
    public function listGroups(Request $request): array
    {
        if (!$request->user()->canUseAdversaryMeter()) {
            throw new \Exception('Missing permission.');
        }
        return [
            'groups' => AssetTagHash::all()->toArray(),
        ];
    }

    #[RpcMethod(
        description: "Get a specific group.",
        params: [
            "group" => "The group hash.",
        ],
        result: [
            "group" => "The group.",
        ]
    )]
    public function getGroup(Request $request): array
    {
        if (!$request->user()->canUseAdversaryMeter()) {
            throw new \Exception('Missing permission.');
        }

        $params = $request->validate([
            'group' => 'required|string|exists:am_assets_tags_hashes,hash',
        ]);
        return [
            'group' => AssetTagHash::query()
                ->where('hash', '=', $params['group'])
                ->firstOrFail(),
        ];
    }

    #[RpcMethod(
        description: "Get the assets that belong to a given group.",
        params: [
            "group" => "The group hash.",
        ],
        result: [
            "msg" => "A success message.",
        ]
    )]
    public function assetsInGroup(Request $request): array
    {
        if (!$request->user()->canUseAdversaryMeter()) {
            throw new \Exception('Missing permission.');
        }

        $params = $request->validate([
            'group' => 'required|string|exists:am_assets_tags_hashes,hash',
        ]);

        /** @var AssetTagHash $group */
        $group = AssetTagHash::where('hash', $params['group'])->firstOrFail();
        $group->views = $group->views + 1;
        $group->update();

        $assets = Asset::select('am_assets.*')
            ->where('am_assets.is_monitored', true)
            ->join('am_assets_tags', 'am_assets_tags.asset_id', '=', 'am_assets.id')
            ->join('am_assets_tags_hashes', 'am_assets_tags_hashes.tag', '=', 'am_assets_tags.tag')
            ->where('am_assets_tags_hashes.hash', $group->hash)
            ->get()
            ->map(fn(Asset $asset) => $this->convertAsset($asset));

        return [
            'assets' => $assets,
        ];
    }

    #[RpcMethod(
        description: "Get the vulnerabilities that belong to a given group.",
        params: [
            "group" => "The group hash.",
        ],
        result: [
            "msg" => "A success message.",
        ]
    )]
    public function vulnerabilitiesInGroup(Request $request): array
    {
        if (!$request->user()->canUseAdversaryMeter()) {
            throw new \Exception('Missing permission.');
        }

        $params = $request->validate([
            'group' => 'required|string|exists:am_assets_tags_hashes,hash',
        ]);

        /** @var AssetTagHash $group */
        $group = AssetTagHash::where('hash', $params['group'])->firstOrFail();
        $group->views = $group->views + 1;
        $group->update();

        $assets = Asset::select('am_assets.*')
            ->where('am_assets.is_monitored', true)
            ->join('am_assets_tags', 'am_assets_tags.asset_id', '=', 'am_assets.id')
            ->join('am_assets_tags_hashes', 'am_assets_tags_hashes.tag', '=', 'am_assets_tags.tag')
            ->where('am_assets_tags_hashes.hash', $group->hash)
            ->get();

        $vulnerabilities = $assets
            ->flatMap(fn(Asset $asset) => $asset->alerts()->get()->map(function (Alert $alert) use ($asset) {
                $port = $alert->port();
                return [
                    'id' => $alert->id,
                    'asset' => $asset->asset,
                    'ip' => $port->ip,
                    'port' => $port->port,
                    'level' => $alert->level,
                    'title' => $alert->title,
                    'vulnerability' => $alert->vulnerability,
                    'remediation' => $alert->remediation,
                    'is_scan_in_progress' => $asset->scanInProgress()->isNotEmpty(),
                ];
            }))
            ->toArray();

        return [
            'vulnerabilities' => $vulnerabilities,
        ];
    }

    #[RpcMethod(
        description: "Mark a vulnerability that belongs to a given group as resolved.",
        params: [
            "group" => "The group hash.",
            "vulnerability_id" => "The vulnerability id.",
        ],
        result: [
            "msg" => "A success message.",
        ]
    )]
    public function resolveVulnerabilityInGroup(Request $request): array
    {
        if (!$request->user()->canUseAdversaryMeter()) {
            throw new \Exception('Missing permission.');
        }

        $params = $request->validate([
            'group' => 'required|string|exists:am_assets_tags_hashes,hash',
            'vulnerability_id' => 'required|integer|exists:am_alerts,id',
        ]);

        /** @var AssetTagHash $group */
        $group = AssetTagHash::query()->where('hash', '=', $params['group'])->firstOrFail();
        /** @var Alert $alert */
        $alert = Alert::find($params['vulnerability_id']);
        $request->replace(['asset_id' => $alert->asset()->id]);
        $this->restartScan($request);

        return [
            'msg' => "The vulnerability has been marked as resolved and will be re-scanned soon.",
        ];
    }

    private function convertAsset(Asset $asset): array
    {
        return [
            'uid' => $asset->id,
            'asset' => $asset->asset,
            'tld' => $asset->tld(),
            'type' => $asset->type->name,
            'status' => $asset->is_monitored ? 'valid' : 'invalid',
            'tags' => $asset->tags()
                ->get()
                ->map(fn(AssetTag $tag) => [
                    'id' => $tag->id,
                    'name' => $tag->tag,
                ])
                ->all(),
        ];
    }
}
