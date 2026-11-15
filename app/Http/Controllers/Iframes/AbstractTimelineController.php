<?php

namespace App\Http\Controllers\Iframes;

use App\Http\Controllers\Controller;
use App\Http\Procedures\EventsProcedure;
use App\Http\Procedures\LeaksProcedure;
use App\Http\Procedures\NotesProcedure;
use App\Http\Procedures\VulnerabilitiesProcedure;
use App\Http\Requests\JsonRpcRequest;
use App\Models\Alert;
use App\Models\Asset;
use App\Models\Conversation;
use App\Models\TimelineItem;
use App\Models\User;
use App\Models\YnhOsquery;
use App\Models\YnhOsqueryRule;
use App\Models\YnhServer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

abstract class AbstractTimelineController extends Controller
{
    // Vulnerability severity ranks: lower is more critical
    private const SEVERITY_CRITICAL = 0;
    private const SEVERITY_HIGH = 1;
    private const SEVERITY_MEDIUM = 2;
    private const SEVERITY_LOW = 3;
    private const SEVERITY_UNKNOWN = 4;

    protected abstract function objects(): string;

    protected abstract function viewname(): string;

    public static function noteAndMemo(User $user, TimelineItem $item): array
    {
        $timestamp = $item->timestamp->utc()->format('Y-m-d H:i:s');
        $date = Str::before($timestamp, ' ');
        $time = Str::beforeLast(Str::after($timestamp, ' '), ':');

        return [
            'timestamp' => $timestamp,
            'date' => $date,
            'time' => $time,
            'html' => \Illuminate\Support\Facades\View::make('theme::iframes.timeline._note', [
                'date' => $date,
                'time' => $time,
                'user' => $user,
                'note' => $item,
            ])->render(),
        ];
    }

    public function __invoke(Request $request): View
    {
        $params = $request->validate([
            'status' => ['nullable', 'string', 'in:monitorable,monitored'],
            'monitoring_type' => ['nullable', 'string', 'in:internal,external'],
            'level' => ['nullable', 'string', 'in:low,medium,high,suspect,other'],
            'server_id' => ['nullable', 'integer', 'exists:ynh_servers,id'],
            'asset_id' => ['nullable', 'integer', 'exists:am_assets,id'],
            'tld' => ['nullable', 'string'],
            'tags' => ['nullable', 'string'], // comma-separated list
            'port_tags' => ['nullable', 'string'], // comma-separated list
            'rule_name' => ['nullable', 'string'],
        ]);

        // Keep in sync with the default value of the window parameter expected by EventsProcedure::list
        $minDate = Carbon::now()->subDays(2)->startOfDay();
        $maxDate = Carbon::now()->endOfDay();

        $objects = $this->objects();
        $items = match ($objects) {
            'assets' => $this->assets(
                $params['status'] ?? null,
                $params['asset_id'] ?? null,
                $params['tld'] ?? null,
                !empty($params['tags']) ?
                    collect(explode(',', $params['tags']))
                        ->map(fn(string $tag) => Str::trim($tag))
                        ->filter(fn(string $tag) => !empty($tag))
                        ->unique()
                        ->values()
                        ->all() :
                    null,
                $params['monitoring_type'] ?? null
            ),
            'conversations' => $this->conversations(),
            'events' => $this->eventsAndIoCs($params['server_id'] ?? null, $params['level'] ?? null, $params['rule_name'] ?? null),
            'leaks' => $this->leaks(
                $params['asset_id'] ?? null,
                $params['tld'] ?? null,
                !empty($params['tags']) ?
                    collect(explode(',', $params['tags']))
                        ->map(fn(string $tag) => Str::trim($tag))
                        ->filter(fn(string $tag) => !empty($tag))
                        ->unique()
                        ->values()
                        ->all() :
                    null
            ),
            'notes-and-memos' => $this->notesAndMemos(),
            'vulnerabilities' => $this->vulnerabilities(
                $params['level'] ?? null,
                $params['asset_id'] ?? null,
                $params['tld'] ?? null,
                !empty($params['tags']) ?
                    collect(explode(',', $params['tags']))
                        ->map(fn(string $tag) => Str::trim($tag))
                        ->filter(fn(string $tag) => !empty($tag))
                        ->unique()
                        ->values()
                        ->all() :
                    null,
                !empty($params['port_tags']) ?
                    collect(explode(',', $params['port_tags']))
                        ->map(fn(string $tag) => Str::trim($tag))
                        ->filter(fn(string $tag) => !empty($tag))
                        ->unique()
                        ->values()
                        ->all() :
                    null
            ),
            default => [],
        };

        if ($objects === 'events') {
            $rules = YnhOsqueryRule::where('enabled', true)->orderBy('name')->get();
            $eventCountsByRule = YnhOsquery::query()
                ->select('ynh_osquery_rule_id', \Illuminate\Support\Facades\DB::raw('count(*) as total'))
                ->join('ynh_servers', 'ynh_servers.id', '=', 'ynh_osquery.ynh_server_id')
                ->join('users', 'users.id', '=', 'ynh_servers.created_by')
                ->where('ynh_osquery.calendar_time', '>=', $minDate)
                ->where('ynh_osquery.calendar_time', '<=', $maxDate)
                ->where('users.tenant_id', Auth::user()->tenant_id)
                ->groupBy('ynh_osquery_rule_id')
                ->pluck('total', 'ynh_osquery_rule_id');
            $eventCountsByServer = YnhOsquery::query()
                ->select('ynh_server_id', \Illuminate\Support\Facades\DB::raw('count(*) as total'))
                ->join('ynh_servers', 'ynh_servers.id', '=', 'ynh_osquery.ynh_server_id')
                ->join('users', 'users.id', '=', 'ynh_servers.created_by')
                ->where('ynh_osquery.calendar_time', '>=', $minDate)
                ->where('ynh_osquery.calendar_time', '<=', $maxDate)
                ->where('users.tenant_id', Auth::user()->tenant_id)
                ->groupBy('ynh_server_id')
                ->pluck('total', 'ynh_server_id');
            $rulesDetails = $rules->mapWithKeys(function (YnhOsqueryRule $rule) use ($eventCountsByRule) {
                return [$rule->name => [
                    'id' => $rule->id,
                    'name' => $rule->name,
                    'display_name' => $rule->displayName(),
                    'nb_events' => $eventCountsByRule->get($rule->id, 0),
                    'description' => $rule->displayDescription(),
                    'platform' => $rule->platform->value,
                    'interval' => \Carbon\CarbonInterval::seconds($rule->interval)->cascade()->forHumans(),
                    'is_ioc' => $rule->is_ioc,
                    'score' => $rule->score,
                    'query' => $rule->query,
                    'tactics' => collect($rule->mitreAttckTactics())->map(fn(string $t) => Str::lower($t))->values(),
                    'mitre' => $rule->attck ? collect(explode(',', $rule->attck))->map(fn(string $uid) => [
                        'uid' => $uid,
                        'url' => Str::startsWith($uid, 'TA') ? "https://attack.mitre.org/tactics/$uid/" : "https://attack.mitre.org/techniques/$uid/"
                    ])->values() : [],
                    'can_edit' => isset($rule->created_by) || \Auth::user()?->isCywiseAdmin(),
                    'editor_url' => route('rules-editor', ['rule_id' => $rule->id]),
                ]];
            })->toArray();
            $serversWithActiveEvents = YnhServer::forUser(Auth::user())
                ->filter(fn(YnhServer $server) => $eventCountsByServer->has($server->id))
                ->map(function (YnhServer $server) use ($eventCountsByServer) {
                    $server->nb_events = $eventCountsByServer->get($server->id, 0);
                    return $server;
                })
                ->sortBy('name')
                ->values();
        }
        return view($this->viewname(), [
            'today_separator' => $this->separator(Carbon::now()),
            'items' => (
            $objects === 'assets' ?
                $items['items']->concat($this->servers($params['server_id'] ?? null, $params['monitoring_type'] ?? null)) :
                $items['items']
            )->sortByDesc('timestamp')
                ->groupBy(fn(array $event) => $event['date'])
                ->mapWithKeys(function ($events, $timestamp) {
                    return [
                        $timestamp => collect($events)
                            ->sortByDesc('time')
                            ->groupBy(fn(array $event) => $event['time'])
                    ];
                })
                ->toArray(),
            'nb_high' => $items['nb_high'] ?? 0,
            'nb_medium' => $items['nb_medium'] ?? 0,
            'nb_low' => $items['nb_low'] ?? 0,
            'nb_suspect' => $items['nb_suspect'] ?? 0,
            'nb_monitored' => $items['nb_monitored'] ?? 0,
            'nb_monitorable' => $items['nb_monitorable'] ?? 0,
            'nb_conversations' => $items['nb_conversations'] ?? 0,
            'nb_notes' => $items['nb_notes'] ?? 0,
            'nb_events' => $items['nb_events'] ?? 0,
            'nb_leaks' => $items['nb_leaks'] ?? 0,
            'rules' => $rules ?? [],
            'rules_details' => $rulesDetails ?? [],
            'selected_rule' => $params['rule_name'] ?? null ? YnhOsqueryRule::where('name', $params['rule_name'])->first() : null,
            'servers_with_active_events' => $serversWithActiveEvents ?? [],
        ]);
    }

    private function separator(Carbon $date): string
    {
        $timestamp = $date->utc()->format('Y-m-d H:i:s');
        $date = Str::before($timestamp, ' ');

        return Str::replace("\n", '', \Illuminate\Support\Facades\View::make('theme::iframes.timeline._separator', [
            'date' => $date,
        ])->render());
    }

    private function servers(?int $serverId = null, ?string $monitoringType = null): Collection
    {
        if ($monitoringType === 'external') {
            return collect();
        }

        /** @var User $user */
        $user = Auth::user();

        return YnhServer::forUser($user)
            ->filter(fn(YnhServer $server) => !$serverId || $serverId === $server->id)
            ->map(function (YnhServer $server) {

                $timestamp = $server->created_at->utc()->format('Y-m-d H:i:s');
                $date = Str::before($timestamp, ' ');
                $time = Str::beforeLast(Str::after($timestamp, ' '), ':');

                return [
                    'timestamp' => $timestamp,
                    'date' => $date,
                    'time' => $time,
                    'html' => \Illuminate\Support\Facades\View::make('theme::iframes.timeline._server', [
                        'date' => $date,
                        'time' => $time,
                        'server' => $server,
                    ])->render(),
                    '_server' => $server,
                ];
            });
    }

    private function assets(?string $status = null, ?int $assetId = null, ?string $tld = null, ?array $tags = null, ?string $monitoringType = null): array
    {
        // Helper to apply shared filters
        $filter = function ($query) use ($assetId, $tld, $tags) {
            return $query
                ->when($assetId, fn($q, $assetId) => $q->where('id', $assetId))
                ->when($tld, fn($q, $tld) => $q->where(fn($q) => $q->where('tld', 'LIKE', '%' . Str::lower($tld) . '%')->orWhere('asset', 'LIKE', '%' . Str::lower($tld) . '%')))
                ->when($tags && count($tags) > 0, function ($q) use ($tags) {
                    $q->whereHas('tags', function ($sub) use ($tags) {
                        $sub->whereIn('tag', $tags);
                    });
                });
        };

        $items = $filter(Asset::query())->get();

        if ($monitoringType) {
            $items = $items->filter(function (Asset $asset) use ($monitoringType) {
                $hasAgent = $asset->hasAgent();
                return $monitoringType === 'internal' ? $hasAgent : !$hasAgent;
            });
        }

        // Counted before the status filter: the KPI tiles double as status filters
        $nbMonitored = $items->where('is_monitored', true)->count();
        $nbMonitorable = $items->where('is_monitored', false)->count();

        if ($status === 'monitorable') {
            $items = $items->where('is_monitored', false);
        } else if ($status === 'monitored') {
            $items = $items->where('is_monitored', true);
        }
        return [
            'nb_monitored' => $nbMonitored,
            'nb_monitorable' => $nbMonitorable,
            'items' => $items->map(function (Asset $asset) {

                $timestamp = $asset->created_at->utc()->format('Y-m-d H:i:s');
                $date = Str::before($timestamp, ' ');
                $time = Str::beforeLast(Str::after($timestamp, ' '), ':');

                $alerts = $asset->is_monitored ?
                    $asset->alerts()->get()->filter(fn(Alert $alert) => $alert->is_hidden === 0) :
                    collect();

                return [
                    'timestamp' => $timestamp,
                    'date' => $date,
                    'time' => $time,
                    'html' => \Illuminate\Support\Facades\View::make('theme::iframes.timeline._asset', [
                        'date' => $date,
                        'time' => $time,
                        'asset' => $asset,
                        'alerts' => $alerts,
                    ])->render(),
                    '_asset' => $asset,
                    '_alerts' => $alerts,
                ];
            }),
        ];
    }

    private function conversations(): array
    {
        /** @var User $user */
        $user = Auth::user();
        $conversations = Conversation::query()
            ->where('created_by', $user->id)
            ->where('dom', '!=', '[]')
            ->get();

        return [
            'nb_conversations' => $conversations->count(),
            'items' => $conversations->map(function (Conversation $conversation) use ($user) {

                $timestamp = $conversation->created_at->utc()->format('Y-m-d H:i:s');
                $date = Str::before($timestamp, ' ');
                $time = Str::beforeLast(Str::after($timestamp, ' '), ':');

                return [
                    'timestamp' => $timestamp,
                    'date' => $date,
                    'time' => $time,
                    'html' => \Illuminate\Support\Facades\View::make('theme::iframes.timeline._conversation', [
                        'date' => $date,
                        'time' => $time,
                        'conversation' => $conversation,
                    ])->render(),
                ];
            }),
        ];
    }

    private function eventsAndIoCs(?int $serverId = null, ?string $level = null, ?string $ruleName = null): array
    {
        $events = $this->events($serverId, $ruleName);
        $iocs = $this->iocs(1, $serverId, $level, $ruleName);

        if ($level === 'suspect' || $level === 'low' || $level === 'medium' || $level === 'high') {
            $items = $iocs['items'];
        } else if ($level === 'other') {
            $items = $events['items'];
        } else {
            $items = collect($events['items'])->concat($iocs['items'])->sortByDesc(fn($item) => $item['timestamp']);
        }
        return [
            'nb_events' => $events['nb_events'],
            'nb_high' => $iocs['nb_high'],
            'nb_medium' => $iocs['nb_medium'],
            'nb_low' => $iocs['nb_low'],
            'nb_suspect' => $iocs['nb_suspect'],
            'items' => $items,
        ];
    }

    private function events(?int $serverId = null, ?string $ruleName = null): array
    {
        $request = new JsonRpcRequest([
            'server_id' => $serverId,
            'min_score' => 0,
            'max_score' => 0,
            'rule_name' => $ruleName,
        ]);
        $request->setUserResolver(fn() => Auth::user());
        $events = (new EventsProcedure())->list($request)['events'];

        return [
            'nb_events' => $events->count(),
            'items' => $events->map(function (YnhOsquery $event) {

                $timestamp = $event->calendar_time->utc()->format('Y-m-d H:i:s');;
                $date = Str::before($timestamp, ' ');
                $time = Str::beforeLast(Str::after($timestamp, ' '), ':');

                return [
                    'timestamp' => $timestamp,
                    'date' => $date,
                    'time' => $time,
                    'html' => \Illuminate\Support\Facades\View::make('theme::iframes.timeline._event', [
                        'date' => $date,
                        'time' => $time,
                        'event' => $event,
                    ])->render(),
                ];
            }),
        ];
    }

    private function iocs(int $minScore = 1, ?int $serverId = null, ?string $level = null, ?string $ruleName = null): array
    {
        $request = new JsonRpcRequest([
            'server_id' => $serverId,
            'min_score' => $minScore,
            'rule_name' => $ruleName,
        ]);
        $request->setUserResolver(fn() => Auth::user());
        $events = (new EventsProcedure())->list($request)['events'];

        $groups = collect();
        /** @var ?Collection $group */
        $group = null;
        /** @var ?int $groupServerId */
        $groupServerId = null;
        /** @var ?string $groupName */
        $groupName = null;
        /** @var ?string $groupDay */
        $groupDay = null;
        $nbHigh = 0;
        $nbMedium = 0;
        $nbLow = 0;
        $nbSuspect = 0;

        /** @var YnhOsquery $event */
        foreach ($events as $event) {
            if ($event->score >= 75) {
                $nbHigh++;
            } else if ($event->score >= 50) {
                $nbMedium++;
            } else if ($event->score >= 25) {
                $nbLow++;
            } else {
                $nbSuspect++;
            }
            if (isset($level)) {
                if ($level === 'high' && $event->score < 75) {
                    continue;
                }
                if ($level === 'medium' && $event->score < 50) {
                    continue;
                }
                if ($level === 'low' && $event->score < 25) {
                    continue;
                }
            }

            $serverId = $event->ynh_server_id ?? null;
            $name = $event->name ?? null;
            $day = $event->calendar_time->utc()->startOfDay()->format('Y-m-d');

            if ($group === null) {
                $group = collect([$event]);
                $groupServerId = $serverId;
                $groupName = $name;
                $groupDay = $day;
            } else {
                if ($serverId === $groupServerId && $name === $groupName && $day === $groupDay) {
                    $group->push($event);
                } else {
                    $groups->push($group);
                    $group = collect([$event]);
                    $groupServerId = $serverId;
                    $groupName = $name;
                    $groupDay = $day;
                }
            }
        }
        if ($group !== null && $group->isNotEmpty()) {
            $groups->push($group);
        }
        return [
            'nb_high' => $nbHigh,
            'nb_medium' => $nbMedium,
            'nb_low' => $nbLow,
            'nb_suspect' => $nbSuspect,
            'items' => $groups->map(function (Collection $group) {

                /** @var YnhOsquery $first */
                $first = $group->first();
                /** @var YnhOsquery $last */
                $last = $group->last();

                $timestampFirst = $first->calendar_time->utc()->format('Y-m-d H:i:s');
                $dateFirst = Str::before($timestampFirst, ' ');
                $timeFirst = Str::beforeLast(Str::after($timestampFirst, ' '), ':');

                $timestampLast = $last->calendar_time->utc()->format('Y-m-d H:i:s');
                $dateLast = Str::before($timestampLast, ' ');
                $timeLast = Str::beforeLast(Str::after($timestampLast, ' '), ':');

                $ioc = [
                    'first' => [
                        'timestamp' => $timestampFirst,
                        'date' => $dateFirst,
                        'time' => $timeFirst,
                        'ioc' => $first,
                    ],
                    'last' => [
                        'timestamp' => $timestampLast,
                        'date' => $dateLast,
                        'time' => $timeLast,
                        'ioc' => $last,
                    ],
                    'in_between' => $group->count(),
                ];

                return [
                    'timestamp' => $timestampFirst,
                    'date' => $dateFirst,
                    'time' => $timeFirst,
                    'html' => \Illuminate\Support\Facades\View::make('theme::iframes.timeline._ioc', [
                        'ioc' => $ioc,
                    ])->render(),
                ];
            }),
        ];
    }

    private function leaks(?int $assetId = null, ?string $asset = null, ?array $tags = null): array
    {
        /** @var User $user */
        $user = Auth::user();
        $request = new JsonRpcRequest();
        $request->setUserResolver(fn() => $user);
        $request->merge([
            'asset_id' => $assetId,
            'asset' => $asset,
            'tags' => $tags,
        ]);
        $leaks = (new LeaksProcedure())->list($request)['leaks'];

        return [
            'nb_leaks' => $leaks->count(),
            'items' => $leaks->chunkWhile(fn(object $leak, int $key, Collection $chunk) => $leak->leak_date === $chunk->last()->leak_date)
                ->map(function (Collection $leaks) use ($user) {

                    $timestamp = $leaks->first()->timestamp->utc()->format('Y-m-d H:i:s');
                    $date = Str::before($timestamp, ' ');
                    $time = Str::beforeLast(Str::after($timestamp, ' '), ':');

                    return [
                        'timestamp' => $timestamp,
                        'date' => $date,
                        'time' => $time,
                        'html' => \Illuminate\Support\Facades\View::make('theme::iframes.timeline._leak', [
                            'date' => $date,
                            'time' => $time,
                            'user' => $user,
                            'leaks' => $leaks,
                        ])->render(),
                    ];
                })->values(),
        ];
    }

    private function notesAndMemos(): array
    {
        /** @var User $user */
        $user = Auth::user();
        $request = new JsonRpcRequest();
        $request->setUserResolver(fn() => $user);
        $notes = (new NotesProcedure())->list($request)['notes'];

        return [
            'nb_notes' => $notes->count(),
            'items' => $notes->map(fn(array $item) => self::noteAndMemo($user, $item['item'])),
        ];
    }

    private function vulnerabilities(?string $level = null, ?int $assetId = null, ?string $tld = null, ?array $tags = null, ?array $portTags = null): array
    {
        $alerts = $this->alerts($assetId, $tld, $tags, $portTags);
        $nbHigh = 0;
        $nbMedium = 0;
        $nbLow = 0;
        $nbSuspect = 0;

        /** @var Alert $alert */
        foreach ($alerts as $alert) {
            if ($alert->isHigh()) {
                $nbHigh++;
            } else if ($alert->isMedium()) {
                $nbMedium++;
            } else if ($alert->isLow()) {
                $nbLow++;
            } else {
                $nbSuspect++;
            }
        }
        if (!empty($level)) {
            $alerts = $alerts->filter(function (Alert $alert) use ($level) {
                if ($level === 'high' && $alert->isHigh()) {
                    return true;
                }
                if ($level === 'medium' && $alert->isMedium()) {
                    return true;
                }
                if ($level === 'low' && $alert->isLow()) {
                    return true;
                }
                return false;
            });
        }
        $userTags = [];

        return [
            'nb_high' => $nbHigh,
            'nb_medium' => $nbMedium,
            'nb_low' => $nbLow,
            'nb_suspect' => $nbSuspect,
            'items' => $alerts->map(function (Alert $alert) use (&$userTags) {

                $timestamp = $alert->updated_at->utc()->format('Y-m-d H:i:s');
                $date = Str::before($timestamp, ' ');
                $time = Str::beforeLast(Str::after($timestamp, ' '), ':');
                $asset = $alert->asset();
                $port = $alert->port;

                // Severity rank, used by the page to list the most critical first
                $severity = match (true) {
                    $alert->isCritical() => self::SEVERITY_CRITICAL,
                    $alert->isHigh() => self::SEVERITY_HIGH,
                    $alert->isMedium() => self::SEVERITY_MEDIUM,
                    $alert->isLow() => self::SEVERITY_LOW,
                    default => self::SEVERITY_UNKNOWN,
                };

                // Searchable asset user tags, queried once per asset (many alerts share one asset)
                $userTags[$asset->id] ??= $asset->tags()->pluck('tag')->implode(' ');

                return [
                    'timestamp' => $timestamp,
                    'date' => $date,
                    'time' => $time,
                    'html' => \Illuminate\Support\Facades\View::make('theme::iframes.timeline._vulnerability', [
                        'date' => $date,
                        'time' => $time,
                        'alert' => $alert,
                        'userTags' => $userTags[$asset->id],
                        'asset' => $asset,
                        'port' => $port,
                    ])->render(),
                    '_asset' => $asset,
                    '_severity' => $severity,
                ];
            }),
        ];
    }

    private function alerts(?int $assetId = null, ?string $asset = null, ?array $tags = null, ?array $portTags = null): Collection
    {
        $request = new JsonRpcRequest([
            'asset_id' => $assetId,
            'asset' => $asset,
            'tags' => $tags,
            'port_tags' => $portTags,
        ]);
        $request->setUserResolver(fn() => Auth::user());
        $alerts = (new VulnerabilitiesProcedure())->list($request);
        return $alerts['high']->concat($alerts['medium'])->concat($alerts['low']);
    }
}
