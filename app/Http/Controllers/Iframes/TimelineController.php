<?php

namespace App\Http\Controllers\Iframes;

use App\Helpers\JosianneClient;
use App\Helpers\Messages;
use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\Asset;
use App\Models\Conversation;
use App\Models\PortTag;
use App\Models\TimelineItem;
use App\Models\User;
use App\Models\YnhOsquery;
use App\Models\YnhServer;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TimelineController extends Controller
{
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
            'level' => ['nullable', 'string', 'in:low,medium,high'],
            'server_id' => ['nullable', 'integer', 'exists:ynh_servers,id'],
            'asset_id' => ['nullable', 'integer', 'exists:am_assets,id'],
        ]);
        $objects = last(explode('/', trim($request->path(), '/')));
        $items = match ($objects) {
            'assets' => $this
                ->assets($params['status'] ?? null, $params['asset_id'] ?? null)
                ->concat($this->servers($params['server_id'] ?? null)),
            'conversations' => $this->conversations(),
            'events' => $this->events($params['server_id'] ?? null),
            'ioc' => $this->ioc(75, $params['server_id'] ?? null),
            'leaks' => $this->leaks(),
            'notes-and-memos' => $this->notesAndMemos(),
            'vulnerabilities' => $this->vulnerabilities($params['level'] ?? null, $params['asset_id'] ?? null),
            default => collect(),
        };
        return view('theme::iframes.timeline', [
            'today_separator' => $this->separator(Carbon::now()),
            'items' => $items->sortByDesc('timestamp')
                ->groupBy(fn(array $event) => $event['date'])
                ->mapWithKeys(function ($events, $timestamp) {
                    return [
                        $timestamp => collect($events)
                            ->sortByDesc('time')
                            ->groupBy(fn(array $event) => $event['time'])
                    ];
                })
                ->toArray(),
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

    private function servers(?int $serverId = null): Collection
    {
        /** @var User $user */
        $user = Auth::user();

        return YnhServer::forUser($user)
            ->filter(fn(YnhServer $server) => !$serverId || $serverId === $server->id)
            ->filter(fn(YnhServer $server) => !$server->isYunoHost())
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

    private function assets(?string $status = null, ?int $assetId = null): Collection
    {
        return Asset::query()
            ->when($status, function ($query, $status) {
                if ($status === 'monitorable') {
                    $query->where('is_monitored', false);
                } else if ($status === 'monitored') {
                    $query->where('is_monitored', true);
                }
            })
            ->when($assetId, fn($query, $assetId) => $query->where('id', $assetId))
            ->get()
            ->map(function (Asset $asset) {

                $timestamp = $asset->created_at->utc()->format('Y-m-d H:i:s');
                $date = Str::before($timestamp, ' ');
                $time = Str::beforeLast(Str::after($timestamp, ' '), ':');

                $alerts = $asset->is_monitored ?
                    $asset->alerts()->get()->filter(fn($alert) => $alert->is_hidden === 0) :
                    collect();
                $hasHigh = $alerts->contains(fn($alert) => $alert->level === 'High');
                $hasMedium = $alerts->contains(fn($alert) => $alert->level === 'Medium');
                $hasLow = $alerts->contains(fn($alert) => $alert->level === 'Low');

                if ($hasHigh) {
                    $bgColor = 'var(--c-red)';
                } elseif ($hasMedium) {
                    $bgColor = 'var(--c-orange-light)';
                } elseif ($hasLow) {
                    $bgColor = 'var(--c-green)';
                } else {
                    $bgColor = 'var(--c-blue)';
                }
                return [
                    'timestamp' => $timestamp,
                    'date' => $date,
                    'time' => $time,
                    'html' => \Illuminate\Support\Facades\View::make('theme::iframes.timeline._asset', [
                        'date' => $date,
                        'time' => $time,
                        'asset' => $asset,
                        'bgColor' => $bgColor,
                        'alerts' => $alerts,
                    ])->render(),
                    '_asset' => $asset,
                ];
            });
    }

    private function conversations(): Collection
    {
        /** @var User $user */
        $user = Auth::user();

        return Conversation::query()
            ->where('created_by', $user->id)
            ->where('dom', '!=', '[]')
            ->get()
            ->map(function (Conversation $conversation) use ($user) {

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
            });
    }

    private function events(?int $serverId = null): Collection
    {
        $cutOffTime = Carbon::now()->startOfDay()->subDay();
        $servers = YnhServer::query()
            ->when($serverId, fn($query, $serverId) => $query->where('id', $serverId))
            ->get();

        return Messages::get($servers, $cutOffTime, [
            Messages::AUTHENTICATION_AND_SSH_ACTIVITY,
            Messages::SERVICES_AND_SCHEDULED_TASKS,
            Messages::PROCESSES_AND_BACKGROUND_TASKS,
            Messages::SHELL_HISTORY_AND_ROOT_COMMANDS,
            Messages::PACKAGES,
            Messages::USERS_AND_GROUPS,
        ])
            ->map(function (array $msg) {

                $timestamp = $msg['timestamp'];
                $date = Str::before($timestamp, ' ');
                $time = Str::beforeLast(Str::after($timestamp, ' '), ':');

                return [
                    'timestamp' => $timestamp,
                    'date' => $date,
                    'time' => $time,
                    'html' => \Illuminate\Support\Facades\View::make('theme::iframes.timeline._event', [
                        'date' => $date,
                        'time' => $time,
                        'msg' => $msg,
                    ])->render(),
                    '_server' => Cache::remember("server_{$msg['ip']}_{$msg['server']}", now()->addHours(3), function () use ($msg) {
                        return YnhServer::where('name', $msg['server'])
                            ->where('ip_address', $msg['ip'])
                            ->first();
                    }),
                ];
            });
    }

    private function ioc(int $minScore = 1, ?int $serverId = null): Collection
    {
        $cutOffTime = Carbon::now()->startOfDay()->subDay();
        $servers = YnhServer::query()->when($serverId, fn($query, $serverId) => $query->where('id', $serverId))->get();
        $events = YnhOsquery::select([
            DB::raw('ynh_servers.name AS server_name'),
            DB::raw('ynh_servers.ip_address AS server_ip_address'),
            'ynh_osquery_rules.score',
            'ynh_osquery_rules.comments',
            'ynh_osquery.*'
        ])
            ->where('ynh_osquery.calendar_time', '>=', $cutOffTime)
            ->join('ynh_osquery_latest_events', 'ynh_osquery_latest_events.ynh_osquery_id', '=', 'ynh_osquery.id')
            ->join('ynh_osquery_rules', 'ynh_osquery_rules.id', '=', 'ynh_osquery.ynh_osquery_rule_id')
            ->join('ynh_servers', 'ynh_servers.id', '=', 'ynh_osquery.ynh_server_id')
            ->whereIn('ynh_osquery_latest_events.ynh_server_id', $servers->pluck('id'))
            ->whereNotExists(function (Builder $query) {
                $query->select(DB::raw(1))
                    ->from('v_dismissed')
                    ->whereColumn('ynh_server_id', '=', 'ynh_osquery.ynh_server_id')
                    ->whereColumn('name', '=', 'ynh_osquery.name')
                    ->whereColumn('action', '=', 'ynh_osquery.action')
                    ->whereColumn('columns_uid', '=', 'ynh_osquery.columns_uid')
                    ->havingRaw('count(1) >=' . Messages::HIDE_AFTER_DISMISS_COUNT);
            })
            ->where('ynh_osquery_rules.is_ioc', true)
            ->where('ynh_osquery_rules.score', '>=', $minScore)
            ->orderBy('calendar_time', 'desc')
            ->get();

        $groups = collect();
        /** @var ?Collection $group */
        $group = null;
        /** @var ?int $groupServerId */
        $groupServerId = null;
        /** @var ?string $groupName */
        $groupName = null;
        /** @var ?string $groupDay */
        $groupDay = null;

        /** @var YnhOsquery $event */
        foreach ($events as $event) {

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
        return $groups->map(function (Collection $group) {

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

            if ($ioc['first']['ioc']->score >= 75) {
                $ioc['first']['txtColor'] = "white";
                $ioc['first']['bgColor'] = "#ff4d4d";
                $ioc['first']['level'] = "(criticité haute)";
            } else if ($ioc['first']['ioc']->score >= 50) {
                $ioc['first']['txtColor'] = "white";
                $ioc['first']['bgColor'] = "#ffaa00";
                $ioc['first']['level'] = "(criticité moyenne)";
            } else if ($ioc['first']['ioc']->score >= 25) {
                $ioc['first']['txtColor'] = "white";
                $ioc['first']['bgColor'] = "#4bd28f";
                $ioc['first']['level'] = "(criticité basse)";
            } else {
                $ioc['first']['txtColor'] = "var(--c-grey-400)";
                $ioc['first']['bgColor'] = "var(--c-grey-100)";
                $ioc['first']['level'] = "(suspect)";
            }
            if ($ioc['last']['ioc']->score >= 75) {
                $ioc['last']['txtColor'] = "white";
                $ioc['last']['bgColor'] = "#ff4d4d";
                $ioc['last']['level'] = "(criticité haute)";
            } else if ($ioc['last']['ioc']->score >= 50) {
                $ioc['last']['txtColor'] = "white";
                $ioc['last']['bgColor'] = "#ffaa00";
                $ioc['last']['level'] = "(criticité moyenne)";
            } else if ($ioc['last']['ioc']->score >= 25) {
                $ioc['last']['txtColor'] = "white";
                $ioc['last']['bgColor'] = "#4bd28f";
                $ioc['last']['level'] = "(criticité basse)";
            } else {
                $ioc['last']['txtColor'] = "var(--c-grey-400)";
                $ioc['last']['bgColor'] = "var(--c-grey-100)";
                $ioc['last']['level'] = "(suspect)";
            }
            return [
                'timestamp' => $timestampFirst,
                'date' => $dateFirst,
                'time' => $timeFirst,
                'html' => \Illuminate\Support\Facades\View::make('theme::iframes.timeline._ioc', [
                    'ioc' => $ioc,
                ])->render(),
            ];
        });
    }

    private function leaks(): Collection
    {
        /** @var User $user */
        $user = Auth::user();
        $now = Carbon::now()->utc()->subDays(30);
        $leaks = TimelineItem::fetchLeaks($user->id, $now, null, 0);

        if ($leaks->isEmpty()) {

            $tlds = "'" . Asset::all()
                    ->map(fn(Asset $asset) => $asset->tld())
                    ->filter(fn(?string $tld) => !empty($tld))
                    ->unique()
                    ->join("','") . "'";

            if ($tlds === "''") {
                $leaks = collect();
            } else {
                $query = "SELECT DISTINCT lower(concat(login, '@', login_email_domain)) AS email, concat(url_scheme, '://', url_subdomain, '.', url_domain) AS website, password FROM dumps_login_email_domain WHERE login_email_domain IN ({$tlds}) ORDER BY email, website ASC";

                Log::debug($query);

                $output = JosianneClient::executeQuery($query);
                $leaks = collect(explode("\n", $output))
                    ->filter(fn(string $line) => !empty($line) && $line !== 'ok')
                    ->map(function (string $line) {
                        return [
                            'email' => Str::trim(Str::before($line, "\t")),
                            'website' => Str::trim(Str::between($line, "\t", "\t")),
                            'password' => $this->maskPassword(Str::trim(Str::afterLast($line, "\t"))),
                        ];
                    })
                    ->map(function (array $credentials) {
                        // if (preg_match("/(?i)\b((?:https?:\/\/|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}\/)(?:[^\s()<>]+|(([^\s()<>]+|(([^\s()<>]+)))*))+(?:(([^\s()<>]+|(([^\s()<>]+)))*)|[^\s`!()[]{};:'\".,<>?«»“”‘’]))/", $credentials['website'])) {
                        if (filter_var($credentials['website'], FILTER_VALIDATE_URL)) {
                            return $credentials;
                        }
                        return [
                            'email' => $credentials['email'],
                            'website' => '',
                            'password' => $credentials['password'],
                        ];
                    })
                    ->unique(fn(array $credentials) => $credentials['email'] . $credentials['website'] . $credentials['password']);
            }
            if (count($leaks) > 0) {

                // Get previous leaks
                $leaksPrev = TimelineItem::fetchLeaks($user->id, null, $now, 0)
                    ->flatMap(fn(TimelineItem $item) => json_decode($item->attributes()['credentials']));

                $leaks = $leaks->filter(function (array $leak) use ($leaksPrev) {
                    return !$leaksPrev->contains(function (object $leakPrev) use ($leak) {
                        return $leakPrev->email === $leak['email'] &&
                            $leakPrev->website === $leak['website'] &&
                            $leakPrev->password === $leak['password'];
                    });
                });

                // Only add the new leaks
                if (count($leaks) > 0) {
                    $leaks->chunk(10)->each(fn(Collection $leaksChunk) => TimelineItem::createLeak($user, $leaksChunk->values()->toArray()));
                }
            }
        }
        return TimelineItem::fetchLeaks($user->id, null, null, 0)
            ->map(function (TimelineItem $item) use ($user) {

                $timestamp = $item->timestamp->utc()->format('Y-m-d H:i:s');
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
                        'leak' => $item,
                    ])->render(),
                ];
            });
    }

    private function notesAndMemos(): Collection
    {
        /** @var User $user */
        $user = Auth::user();

        return TimelineItem::fetchNotes($user->id, null, null, 0)
            ->map(fn(TimelineItem $item) => self::noteAndMemo($user, $item));
    }

    private function vulnerabilities(?string $level = null, ?int $assetId = null): Collection
    {
        return $this->alerts($level, $assetId)
            ->map(function (Alert $alert) {

                $timestamp = $alert->updated_at->utc()->format('Y-m-d H:i:s');
                $date = Str::before($timestamp, ' ');
                $time = Str::beforeLast(Str::after($timestamp, ' '), ':');
                $asset = $alert->asset();
                $port = $alert->port();

                if ($alert->level === 'High') {
                    $txtColor = "white";
                    $bgColor = "var(--c-red)";
                    $level = "(" . __("high") . ")";
                } else if ($alert->level === 'Medium') {
                    $txtColor = "white";
                    $bgColor = "var(--c-orange-light)";
                    $level = "(" . __("medium") . ")";
                } else if ($alert->level === 'Low') {
                    $txtColor = "white";
                    $bgColor = "var(--c-green)";
                    $level = "(" . __("low") . ")";
                } else {
                    $txtColor = "var(--c-grey-400)";
                    $bgColor = "var(--c-grey-100)";
                    $level = "(" . __("inconnue") . ")";
                }

                $tags = "<div><span class='lozenge new' style='font-size: 0.8rem;margin-top: 3px;'>" . $port
                        ->tags()
                        ->get()
                        ->map(fn(PortTag $tag) => Str::lower($tag->tag))
                        ->join("</span>&nbsp;<span class='lozenge new' style='font-size: 0.8rem;margin-top: 3px;'>") . "</span></div>";

                return [
                    'timestamp' => $timestamp,
                    'date' => $date,
                    'time' => $time,
                    'html' => \Illuminate\Support\Facades\View::make('theme::iframes.timeline._vulnerability', [
                        'date' => $date,
                        'time' => $time,
                        'txtColor' => $txtColor,
                        'bgColor' => $bgColor,
                        'level' => $level,
                        'tags' => $tags,
                        'alert' => $alert,
                        'asset' => $asset,
                        'port' => $port,
                    ])->render(),
                    '_asset' => $asset,
                ];
            });
    }

    private function alerts(?string $level = null, ?int $assetId = null): Collection
    {
        return Asset::where('is_monitored', true)
            ->when($assetId, fn($query, $assetId) => $query->where('id', $assetId))
            ->get()
            ->flatMap(fn(Asset $asset) => $asset->alerts()->when($level, function ($query, $level) {
                if ($level === 'high') {
                    $query->where('level', 'High');
                } else if ($level === 'medium') {
                    $query->where('level', 'Medium');
                } else if ($level === 'low') {
                    $query->where('level', 'Low');
                }
            })->get())
            ->filter(fn(Alert $alert) => $alert->is_hidden === 0);
    }

    private function maskPassword(string $password, int $size = 3): string
    {
        if (Str::length($password) <= 2) {
            return Str::repeat('*', Str::length($password));
        }
        if (Str::length($password) <= 2 * $size) {
            return $this->maskPassword($password, 1);
        }
        return Str::substr($password, 0, $size) . Str::repeat('*', Str::length($password) - 2 * $size) . Str::substr($password, -$size, $size);
    }
}
