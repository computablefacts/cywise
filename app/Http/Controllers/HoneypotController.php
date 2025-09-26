<?php

namespace App\Http\Controllers;

use App\Enums\HoneypotCloudProvidersEnum;
use App\Enums\HoneypotCloudSensorsEnum;
use App\Enums\HoneypotStatusesEnum;
use App\Http\Procedures\AssetsProcedure;
use App\Http\Requests\JsonRpcRequest;
use App\Mail\HoneypotRequested;
use App\Mail\MailCoachHoneypotRequested;
use App\Models\Alert;
use App\Models\Asset;
use App\Models\AssetTagHash;
use App\Models\Attacker;
use App\Models\HiddenAlert;
use App\Models\Honeypot;
use App\Models\HoneypotEvent;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/** @deprecated */
class HoneypotController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function attackerIndex(Request $request): array
    {
        $honeypots = Honeypot::all()->pluck('id');
        $totalNumberOfEvents = HoneypotEvent::count();
        return Attacker::select('am_attackers.*')
            ->join('am_honeypots_events', 'am_honeypots_events.attacker_id', '=', 'am_attackers.id')
            ->whereIn('am_honeypots_events.honeypot_id', $honeypots)
            ->orderBy('am_attackers.name')
            ->orderBy('am_attackers.last_contact')
            ->distinct()
            ->get()
            ->map(function (Attacker $attacker) use ($totalNumberOfEvents) {
                return [
                    'id' => $attacker->id,
                    'name' => $attacker->name,
                    'first_contact' => $attacker->first_contact->format('Y-m-d H:i') . ' UTC',
                    'last_contact' => $attacker->last_contact->format('Y-m-d H:i') . ' UTC',
                    'aggressiveness' => $attacker->aggressiveness($totalNumberOfEvents),
                    'ips' => $attacker->ips(),
                ];
            })
            ->toArray();
    }

    public function recentEvents(Request $request): array
    {
        $manual = $request->boolean('manual', true);
        $auto = $request->boolean('auto', true);

        if (!$manual && !$auto) {
            return [];
        }

        /** @var array $ips */
        $ips = config('towerify.adversarymeter.ip_addresses');
        $honeypots = Honeypot::all()->pluck('id');
        $events = HoneypotEvent::select(
            'am_honeypots_events.*',
            DB::raw("CASE WHEN am_attackers.name IS NULL THEN '-' ELSE am_attackers.name END AS internal_name"),
            DB::raw("CASE WHEN am_attackers.id IS NULL THEN '-' ELSE am_attackers.id END AS attacker_id"),
        )
            ->whereIn('honeypot_id', $honeypots)
            ->whereNotIn('ip', $ips)
            ->leftJoin('am_attackers', 'am_attackers.id', '=', 'am_honeypots_events.attacker_id');

        if ($auto && !$manual) {
            $events->where('human', true);
        } else if (!$auto && $manual) {
            $events->where('targeted', true);
        }
        return $events->orderBy('timestamp', 'desc')
            ->limit(1000)
            ->get()
            ->toArray();
    }

    public function blacklistIps(?int $attackerId = null)
    {
        /** @var array $ips */
        $ips = config('towerify.adversarymeter.ip_addresses');
        $honeypots = Honeypot::all()->pluck('id');
        $events = HoneypotEvent::select(
            'am_honeypots_events.ip',
            DB::raw('MIN(am_honeypots_events.timestamp) AS first_contact'),
            DB::raw('MAX(am_honeypots_events.timestamp) AS last_contact'),
            DB::raw("MAX(am_honeypots_events.hosting_service_description) AS isp_name"),
            DB::raw("MAX(am_honeypots_events.hosting_service_country_code) AS country_code"),
        )
            ->whereIn('honeypot_id', $honeypots)
            ->whereNotIn('am_honeypots_events.ip', $ips)
            ->join('am_attackers', 'am_attackers.id', '=', 'am_honeypots_events.attacker_id');
        if ($attackerId) {
            $events->where('am_honeypots_events.attacker_id', $attackerId);
        }
        return $events->groupBy('ip')->distinct()->get()->toArray();
    }

    public function getVulnerabilitiesWithAssetInfo(?int $attackerId = null): array
    {
        return Asset::all()
            ->flatMap(function (Asset $asset) use ($attackerId) {
                return $asset->alerts()
                    ->get()
                    ->filter(fn(Alert $alert) => !$attackerId || ($alert->cve_id && $alert->events($attackerId)->exists()))
                    ->filter(fn(Alert $alert) => $attackerId || !$alert->is_hidden)
                    ->map(function (Alert $alert) use ($asset, $attackerId) {
                        return [
                            'alert' => $alert,
                            'asset' => $asset,
                            'port' => $alert->port(),
                            'events' => $alert->cve_id ? $alert->events($attackerId)->get()->toArray() : [],
                        ];
                    });
            })
            ->toArray();
    }

    public function getVulnerabilitiesWithAssetInfo2(string $assetBase64): array
    {
        return Asset::where('asset', base64_decode($assetBase64))
            ->get()
            ->flatMap(function (Asset $asset) {
                return $asset->alerts()
                    ->get()
                    ->map(function (Alert $alert) use ($asset) {
                        return [
                            'alert' => $alert,
                            'asset' => $asset,
                            'events' => $alert->events()->get()->toArray(),
                        ];
                    });
            })
            ->toArray();
    }

    public function attackerActivity(Attacker $attacker): array
    {
        $honeypots = Honeypot::all()->pluck('id');
        $events = $attacker->events()->whereIn('honeypot_id', $honeypots)->orderBy('timestamp', 'desc')->get();
        return [
            'firstEvent' => $events->pluck('timestamp')->last()?->format('Y-m-d H:i') . ' UTC',
            'top3EventTypes' => $events->groupBy('event')
                ->sort(fn($e1, $e2) => $e2->count() - $e1->count())
                ->take(3)
                ->map(fn($events, $type) => [
                    'type' => $type,
                    'count' => $events->count(),
                ])
                ->values(),
            'events' => $events->take(1000)->toArray(),
        ];
    }

    public function attackerProfile(Attacker $attacker): array
    {
        $honeypots = Honeypot::all()->pluck('id');
        return [
            'id' => $attacker->id,
            'name' => $attacker->name,
            'first_contact' => $attacker->first_contact->format('Y-m-d H:i') . ' UTC',
            'last_contact' => $attacker->last_contact->format('Y-m-d H:i') . ' UTC',
            'count' => $attacker->events()->whereIn('honeypot_id', $honeypots)->count(),
            'tot' => HoneypotEvent::whereIn('honeypot_id', $honeypots)->count(),
            'aggressiveness' => $attacker->aggressiveness(),
        ];
    }

    public function attackerStats(Attacker $attacker): array
    {
        $honeypots = Honeypot::all()->pluck('id');
        return [
            'attacks' => $attacker->events()->whereIn('honeypot_id', $honeypots)->count(),
            'human' => $attacker->humans()->whereIn('honeypot_id', $honeypots)->count(),
            'targeted' => $attacker->targeted()->whereIn('honeypot_id', $honeypots)->count(),
            'cve' => $attacker->cves()->count(),
        ];
    }

    public function getMostRecentEvent(?int $attackerId = null): array
    {
        $honeypots = Honeypot::all()->pluck('id');
        if ($attackerId) {
            return Attacker::find($attackerId)
                ->events()
                ->whereIn('honeypot_id', $honeypots)
                ->orderBy('timestamp', 'desc')
                ->limit(3)
                ->get()
                ->toArray();
        }
        return HoneypotEvent::query()
            ->whereIn('honeypot_id', $honeypots)
            ->orderBy('timestamp', 'desc')
            ->limit(3)
            ->get()
            ->toArray();
    }

    public function attackerTools(Attacker $attacker): array
    {
        return $attacker->tools()->toArray();
    }

    public function attackerCompetency(Attacker $attacker): array
    {
        $honeypots = Honeypot::all()->pluck('id');
        $tools = $attacker->tools()->count();
        $cves = $attacker->cves()->count();
        $humans = $attacker->humans()->whereIn('honeypot_id', $honeypots)->count();
        $ips = $attacker->ips()->count();
        $targetedWordlists = $attacker->events()
            ->whereIn('honeypot_id', $honeypots)
            ->where('event', 'curated_wordlist')
            ->count();
        $targetedPasswords = $attacker->events()
            ->whereIn('honeypot_id', $honeypots)
            ->where('event', 'manual_actions_password_targeted')
            ->count();
        return [
            'toolbox' => min($tools * 1, 10),
            'cve_collection' => min($cves / 1000 * 100, 10),
            'manual_testing' => min($humans / 50 * 100, 10),
            'stealth_tech' => min($ips / 5 * 10, 10),
            'curated_wordlist' => $targetedPasswords ? 10 : min($targetedWordlists / 50 * 100, 10),
            'persistence' => min($attacker->first_contact->diffInDays($attacker->last_contact) / 10 * 10, 10),
        ];
    }

    public function lastHoneypots(): array
    {
        return Honeypot::query()
            ->orderBy('dns')
            ->limit(3)
            ->get()
            ->toArray();
    }

    public function getHoneypotEventStats(string $dns): array
    {
        $honeypots = Honeypot::all()->pluck('id');
        return HoneypotEvent::select(
            DB::raw("DATE_FORMAT(timestamp, '%Y-%m-%d') AS date"),
            DB::raw("SUM(CASE WHEN human = 1 OR targeted = 1 THEN 1 ELSE 0 END) AS human_or_targeted"),
            DB::raw("SUM(CASE WHEN human = 0 AND targeted = 0 THEN 1 ELSE 0 END) AS not_human_or_targeted")
        )
            ->join('am_honeypots', 'am_honeypots.id', '=', 'am_honeypots_events.honeypot_id')
            ->where('am_honeypots.dns', $dns)
            ->whereIn('am_honeypots_events.honeypot_id', $honeypots)
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->limit(30)
            ->get()
            ->toArray();
    }

    public function getAlertStats(): array
    {
        $nbVulnerabilities = Asset::all()
            ->map(function (Asset $asset) {
                return [
                    'high' => $asset->alerts()->where('level', 'High')->count(),
                    'high_unverified' => $asset->alerts()->where('level', 'High (unverified)')->count(),
                    'medium' => $asset->alerts()->where('level', 'Medium')->count(),
                    'low' => $asset->alerts()->where('level', 'Low')->count(),
                ];
            })
            ->reduce(function (array $carry, array $counts) {
                return [
                    'high' => $carry['high'] + $counts['high'],
                    'high_unverified' => $carry['high_unverified'] + $counts['high_unverified'],
                    'medium' => $carry['medium'] + $counts['medium'],
                    'low' => $carry['low'] + $counts['low'],
                ];
            }, [
                'high' => 0,
                'high_unverified' => 0,
                'medium' => 0,
                'low' => 0,
            ]);
        return [
            'High' => $nbVulnerabilities['high'],
            'High (unverified)' => $nbVulnerabilities['high_unverified'],
            'Medium' => $nbVulnerabilities['medium'],
            'Low' => $nbVulnerabilities['low'],
        ];
    }

    public function honeypotsStatus(): array
    {
        $statuses = [
            HoneypotStatusesEnum::SETUP_COMPLETE,
            HoneypotStatusesEnum::HONEYPOT_SETUP,
            HoneypotStatusesEnum::DNS_SETUP
        ];

        $honeypots = Honeypot::all();

        /** @var HoneypotStatusesEnum $leastAdvancedStatus */
        $leastAdvancedStatus = $honeypots->reduce(function (HoneypotStatusesEnum $carry, Honeypot $honeypot) use ($statuses) {
            $currentStatusIndex = array_search($honeypot->status(), $statuses);
            $carryIndex = array_search($carry, $statuses);
            return $currentStatusIndex < $carryIndex ? $honeypot->status() : $carry;
        }, HoneypotStatusesEnum::DNS_SETUP);

        return [
            'current_user' => Auth::user()->name,
            'honeypots' => $honeypots,
            'integration_status' => $honeypots->count() ? $leastAdvancedStatus : 'inactive'
        ];
    }

    public function postHoneypots(Request $request): array
    {
        $honeypots = $request->validate([
            'honeypots' => [
                'required',
                'array',
                'min:1',
                'max:3',
            ],
            'honeypots.*.dns' => 'regex:/^(?!-)[A-Za-z0-9-]+(\.[A-Za-z0-9-]+)*\.[A-Za-z]{2,}$/',
            'honeypots.*.cloud_provider' => 'string|required',
            'honeypots.*.sensor' => 'string|required',
        ])['honeypots'];

        $count = Honeypot::count();

        if ($count + count($honeypots) > 3) {
            abort(500, 'You already have the maximum number of honeypots allowed : 3.');
        }
        foreach ($honeypots as $honeypot) {
            if (Honeypot::where('dns', $honeypot['dns'])->exists()) {
                abort(500, "HoneyPots {$honeypot['dns']} already exists");
            }
            Honeypot::create([
                'dns' => $honeypot['dns'],
                'status' => HoneypotStatusesEnum::DNS_SETUP,
                'cloud_provider' => HoneypotCloudProvidersEnum::AWS,
                'cloud_sensor' => HoneypotCloudSensorsEnum::from($honeypot['sensor']),
            ]);
        }
        return Honeypot::query()
            ->orderBy('dns')
            ->get()
            ->toArray();
    }

    public function moveHoneypotsConfigurationToNextStep(): void
    {
        Honeypot::where('status', '!=', HoneypotStatusesEnum::SETUP_COMPLETE)
            ->get()
            ->each(function (Honeypot $honeypot) {

                $statuses = [
                    HoneypotStatusesEnum::DNS_SETUP,
                    HoneypotStatusesEnum::HONEYPOT_SETUP,
                    HoneypotStatusesEnum::SETUP_COMPLETE,
                ];

                $nextIdx = array_search($honeypot->status, $statuses) + 1;
                $honeypot->status = $statuses[$nextIdx];
                $honeypot->save();

                if ($statuses[$nextIdx] === HoneypotStatusesEnum::HONEYPOT_SETUP) {
                    /** @var User $user */
                    $user = Auth::user();
                    $subject = "Setup of honeypot {$honeypot->dns} requested";
                    $body = [
                        'id' => $honeypot->id,
                        'sensor' => $honeypot->cloud_sensor,
                        'provider' => $honeypot->cloud_provider,
                        'query' => "UPDATE am_honeypots SET status = 'setup_complete' WHERE id = {$honeypot->id};",
                    ];
                    MailCoachHoneypotRequested::sendEmail($honeypot->id, $honeypot->cloud_sensor, $honeypot->cloud_provider, $honeypot->dns);
                    Mail::to(config('towerify.freshdesk.to_email'))->send(new HoneypotRequested($user->email, $user->name, $subject, $body));
                }
            });
    }

    public function assetTags(): array
    {
        return (new AssetsProcedure())->listTags(new JsonRpcRequest());
    }

    public function getHashes(): array
    {
        return (new AssetsProcedure())->listGroups(new JsonRpcRequest())['groups'];
    }

    public function createHash(Request $request): AssetTagHash
    {
        return (new AssetsProcedure())->group(JsonRpcRequest::createFrom($request))['group'];
    }

    public function deleteHash(AssetTagHash $hash): JsonResponse
    {
        $request = new Request(['group' => $hash->hash]);
        $request->setUserResolver(fn() => auth()->user());
        return response()->json([
            'message' => (new AssetsProcedure())->degroup(JsonRpcRequest::createFrom($request))['msg']
        ]);
    }

    public function createHiddenAlert(Request $request): HiddenAlert
    {
        $payload = $request->validate([
            'uid' => 'nullable|string',
            'type' => 'nullable|string',
            'title' => 'nullable|string',
        ]);

        $uid = trim($request->string('uid'));
        $type = trim($request->string('type'));
        $title = trim($request->string('title'));

        if (empty($uid) && empty($type) && empty($title)) {
            abort(500, 'At least one of uid, type or title must be present.');
        }

        $hiddenAlerts = HiddenAlert::query();

        if (!empty($uid)) {
            $hiddenAlerts->where('uid', $uid);
        } else if (!empty($type)) {
            $hiddenAlerts->where('type', $type);
        } else if (!empty($title)) {
            $hiddenAlerts->where('title', $title);
        }

        $hidden = $hiddenAlerts->first();

        if (!$hidden) {
            $hidden = HiddenAlert::create([
                'uid' => $uid,
                'type' => $type,
                'title' => $title,
            ]);
        }
        return $hidden;
    }

    public function deleteHiddenAlert(HiddenAlert $hiddenAlert): void
    {
        $hiddenAlert->delete();
    }
}
