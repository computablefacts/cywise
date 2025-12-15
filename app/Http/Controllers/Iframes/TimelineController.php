<?php

namespace App\Http\Controllers\Iframes;

use App\Helpers\JosianeClient;
use App\Helpers\Messages;
use App\Http\Controllers\Controller;
use App\Http\Procedures\EventsProcedure;
use App\Http\Procedures\VulnerabilitiesProcedure;
use App\Http\Requests\JsonRpcRequest;
use App\Models\Alert;
use App\Models\Asset;
use App\Models\Conversation;
use App\Models\PortTag;
use App\Models\TimelineItem;
use App\Models\User;
use App\Models\YnhOsquery;
use App\Models\YnhServer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TimelineController extends Controller
{
    public static function fetchLeaks(User $user): Collection
    {
        $now = Carbon::now()->utc()->subDays(15);
        $leaks = TimelineItem::fetchLeaks($user->id, $now, null, 0);

        if ($leaks->isEmpty()) {

            $tlds = "'" . Asset::select('am_assets.*')
                    ->join('users', 'users.id', '=', 'am_assets.created_by')
                    ->when($user->tenant_id, fn($query, $tenantId) => $query->where('users.tenant_id', $tenantId))
                    ->when($user->customer_id, fn($query, $customerId) => $query->where('users.customer_id', $customerId))
                    ->get()
                    ->map(fn(Asset $asset) => $asset->tld())
                    ->filter(fn(?string $tld) => !empty($tld))
                    ->unique()
                    ->join("','") . "'";

            if ($tlds === "''") {
                $leaks = collect();
            } else {
                $query = "
                  SELECT DISTINCT 
                    min(db_date) AS leak_date, 
                    lower(concat(login, '@', login_email_domain)) AS email, 
                    concat(url_scheme, '://', url_subdomain, '.', url_domain) AS website, 
                    password
                  FROM dumps_login_email_domain 
                  WHERE login_email_domain IN ({$tlds})
                  GROUP BY email, website, password
                  ORDER BY email, website ASC
                ";

                // Log::debug($query);

                $output = JosianeClient::executeQuery($query);
                $leaks = collect(explode("\n", $output))
                    ->filter(fn(string $line) => !empty($line) && $line !== 'ok')
                    ->map(function (string $line) {
                        $obj = explode("\t", $line);
                        return [
                            'leak_date' => Str::before(Str::trim($obj[0]), ' '),
                            'email' => Str::trim($obj[1] ?? ''),
                            'website' => Str::trim($obj[2] ?? ''),
                            'password' => self::maskPassword(Str::trim($obj[3] ?? '')),
                        ];
                    })
                    ->map(function (array $credentials) {
                        // if (preg_match("/(?i)\b((?:https?:\/\/|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}\/)(?:[^\s()<>]+|(([^\s()<>]+|(([^\s()<>]+)))*))+(?:(([^\s()<>]+|(([^\s()<>]+)))*)|[^\s`!()[]{};:'\".,<>?«»“”‘’]))/", $credentials['website'])) {
                        if (filter_var($credentials['website'], FILTER_VALIDATE_URL)) {
                            return $credentials;
                        }
                        return [
                            'leak_date' => $credentials['leak_date'],
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
        return TimelineItem::fetchLeaks($user->id, null, null, 0);
    }

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

    private static function maskPassword(string $password, int $size = 3): string
    {
        if (Str::length($password) <= 2) {
            return Str::repeat('*', Str::length($password));
        }
        if (Str::length($password) <= 2 * $size) {
            return self::maskPassword($password, 1);
        }
        return Str::substr($password, 0, $size) . Str::repeat('*', Str::length($password) - 2 * $size) . Str::substr($password, -$size, $size);
    }

    public function __invoke(Request $request): View
    {
        $params = $request->validate([
            'status' => ['nullable', 'string', 'in:monitorable,monitored'],
            'level' => ['nullable', 'string', 'in:low,medium,high'],
            'server_id' => ['nullable', 'integer', 'exists:ynh_servers,id'],
            'asset_id' => ['nullable', 'integer', 'exists:am_assets,id'],
            'tld' => ['nullable', 'string'],
            'tags' => ['nullable', 'string'], // comma-separated list
        ]);
        $objects = last(explode('/', trim($request->path(), '/')));
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
                    null
            ),
            'conversations' => $this->conversations(),
            'events' => $this->events($params['server_id'] ?? null),
            'ioc' => $this->iocs(10, $params['server_id'] ?? null, $params['level'] ?? null),
            'leaks' => $this->leaks(),
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
                    null
            ),
            default => [],
        };
        return view('theme::iframes.timeline', [
            'today_separator' => $this->separator(Carbon::now()),
            'items' => (
            $objects === 'assets' ?
                $items['items']->concat($this->servers($params['server_id'] ?? null)) :
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

    private function assets(?string $status = null, ?int $assetId = null, ?string $tld = null, ?array $tags = null): array
    {
        // Helper to apply shared filters
        $filter = function ($query) use ($assetId, $tld, $tags) {
            return $query
                ->when($assetId, fn($q, $assetId) => $q->where('id', $assetId))
                ->when($tld, fn($q, $tld) => $q->where('tld', Str::lower($tld)))
                ->when($tags && count($tags) > 0, function ($q) use ($tags) {
                    $q->whereHas('tags', function ($sub) use ($tags) {
                        $sub->whereIn('tag', $tags);
                    });
                });
        };
        return [
            'nb_monitored' => $filter(Asset::query())
                ->where('is_monitored', true)
                ->count(),
            'nb_monitorable' => $filter(Asset::query())
                ->where('is_monitored', false)
                ->count(),
            'items' => $filter(Asset::query())
                ->when($status, function ($query, $status) {
                    if ($status === 'monitorable') {
                        $query->where('is_monitored', false);
                    } else if ($status === 'monitored') {
                        $query->where('is_monitored', true);
                    }
                })
                ->get()
                ->map(function (Asset $asset) {

                    $timestamp = $asset->created_at->utc()->format('Y-m-d H:i:s');
                    $date = Str::before($timestamp, ' ');
                    $time = Str::beforeLast(Str::after($timestamp, ' '), ':');

                    $alerts = $asset->is_monitored ?
                        $asset->alerts()->get()->filter(fn(Alert $alert) => $alert->is_hidden === 0) :
                        collect();
                    $hasHigh = $alerts->contains(fn(Alert $alert) => $alert->isHigh());
                    $hasMedium = $alerts->contains(fn(Alert $alert) => $alert->isMedium());
                    $hasLow = $alerts->contains(fn(Alert $alert) => $alert->isLow());

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

    private function events(?int $serverId = null): array
    {
        $cutOffTime = Carbon::now()->startOfDay()->subDays(3);
        $servers = YnhServer::query()
            ->when($serverId, fn($query, $serverId) => $query->where('id', $serverId))
            ->get();
        $events = Messages::get($servers, $cutOffTime, [
            Messages::AUTHENTICATION_AND_SSH_ACTIVITY,
            // Messages::SERVICES_AND_SCHEDULED_TASKS,
            Messages::SHELL_HISTORY_AND_ROOT_COMMANDS,
            Messages::PACKAGES,
            Messages::USERS_AND_GROUPS,
        ]);

        return [
            'nb_events' => $events->count(),
            'items' => $events->map(function (array $msg) {

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
            }),
        ];
    }

    private function iocs(int $minScore = 1, ?int $serverId = null, ?string $level = null): array
    {
        $request = new JsonRpcRequest([
            'server_id' => $serverId,
            'min_score' => $minScore,
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
            }),
        ];
    }

    private function leaks(): array
    {
        /** @var User $user */
        $user = Auth::user();
        $leaks = self::fetchLeaks($user);

        return [
            'nb_leaks' => $leaks->count(),
            'items' => $leaks->map(function (TimelineItem $item) use ($user) {

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
            }),
        ];
    }

    private function notesAndMemos(): array
    {
        /** @var User $user */
        $user = Auth::user();
        $notes = TimelineItem::fetchNotes($user->id, null, null, 0);

        return [
            'nb_notes' => $notes->count(),
            'items' => $notes->map(fn(TimelineItem $item) => self::noteAndMemo($user, $item)),
        ];
    }

    private function vulnerabilities(?string $level = null, ?int $assetId = null, ?string $tld = null, ?array $tags = null): array
    {
        $alerts = $this->alerts($assetId, $tld, $tags);
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
        return [
            'nb_high' => $nbHigh,
            'nb_medium' => $nbMedium,
            'nb_low' => $nbLow,
            'nb_suspect' => $nbSuspect,
            'items' => $alerts->map(function (Alert $alert) {

                $timestamp = $alert->updated_at->utc()->format('Y-m-d H:i:s');
                $date = Str::before($timestamp, ' ');
                $time = Str::beforeLast(Str::after($timestamp, ' '), ':');
                $asset = $alert->asset();
                $port = $alert->port;

                if ($alert->isHigh()) {
                    $txtColor = "white";
                    $bgColor = "var(--c-red)";
                    $level = "(" . __("high") . ")";
                } else if ($alert->isMedium()) {
                    $txtColor = "white";
                    $bgColor = "var(--c-orange-light)";
                    $level = "(" . __("medium") . ")";
                } else if ($alert->isLow()) {
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
            }),
        ];
    }

    private function alerts(?int $assetId = null, ?string $tld = null, ?array $tags = null): Collection
    {
        $request = new JsonRpcRequest([
            'asset_id' => $assetId,
            'tld' => $tld,
            'tags' => $tags,
        ]);
        $request->setUserResolver(fn() => Auth::user());
        $alerts = (new VulnerabilitiesProcedure())->list($request);
        return $alerts['high']->concat($alerts['medium'])->concat($alerts['low']);
    }
}
