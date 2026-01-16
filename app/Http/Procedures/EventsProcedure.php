<?php

namespace App\Http\Procedures;

use App\Helpers\Messages;
use App\Http\Requests\JsonRpcRequest;
use App\Models\YnhOsquery;
use App\Models\YnhServer;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Sajya\Server\Attributes\RpcMethod;
use Sajya\Server\Procedure;

class EventsProcedure extends Procedure
{
    public static string $name = 'events';

    #[RpcMethod(
        description: "List collected events.",
        params: [
            "min_score" => "A score of 0 indicates a system event; any score above 0 indicates an IoC, with values closer to 100 reflecting a higher probability of compromise.",
            "server_id" => "An optional server id.",
            "window" => "An optional window of time [min_date, max_date] to filter events by."
        ],
        result: [
            "events" => "The list of events over the last 3 days.",
        ]
    )]
    public function list(JsonRpcRequest $request): array
    {
        $params = $request->validate([
            'min_score' => 'required|integer|min:0|max:100',
            'server_id' => 'nullable|integer|exists:ynh_servers,id',
            'window' => 'nullable|array|min:2|max:2',
            'window.*' => 'required|date',
        ]);

        $serverId = $params['server_id'] ?? null;
        $minScore = $params['min_score'] ?? 0;

        if (isset($params['window'])) {
            $minDate = Carbon::createFromFormat('Y-m-d', $params['window'][0])->startOfDay();
            $maxDate = Carbon::createFromFormat('Y-m-d', $params['window'][1])->endOfDay();
        } else {
            $maxDate = Carbon::now()->endOfDay();
            $minDate = $maxDate->subDays(3)->startOfDay();
        }

        $servers = YnhServer::query()->when($serverId, fn($query, $serverId) => $query->where('id', $serverId))->get();
        $events = YnhOsquery::select([
            DB::raw('ynh_servers.name AS server_name'),
            DB::raw('ynh_servers.ip_address AS server_ip_address'),
            'ynh_osquery_rules.score',
            'ynh_osquery_rules.comments',
            'ynh_osquery.*'
        ])
            ->join('ynh_osquery_rules', 'ynh_osquery_rules.id', '=', 'ynh_osquery.ynh_osquery_rule_id')
            ->join('ynh_servers', 'ynh_servers.id', '=', 'ynh_osquery.ynh_server_id')
            ->where('ynh_osquery.calendar_time', '>=', $minDate)
            ->where('ynh_osquery.calendar_time', '<=', $maxDate)
            ->whereIn('ynh_osquery.ynh_server_id', $servers->pluck('id'))
            ->whereNotExists(function (Builder $query) {
                $query->select(DB::raw(1))
                    ->from('v_dismissed')
                    ->whereColumn('ynh_server_id', '=', 'ynh_osquery.ynh_server_id')
                    ->whereColumn('name', '=', 'ynh_osquery.name')
                    ->whereColumn('action', '=', 'ynh_osquery.action')
                    ->whereColumn('columns_uid', '=', 'ynh_osquery.columns_uid')
                    ->havingRaw('count(1) >=' . Messages::HIDE_AFTER_DISMISS_COUNT);
            })
            ->where('ynh_osquery_rules.score', '>=', $minScore);

        if ($minScore > 0) {
            $events = $events->where('ynh_osquery_rules.is_ioc', true);
        }
        return [
            'events' => $events->orderBy('calendar_time', 'desc')->get(),
        ];
    }

    #[RpcMethod(
        description: "Dismiss an event (false positive).",
        params: [
            'event_id' => 'The event identifier.',
        ],
        result: [
            "msg" => "A success message.",
        ]
    )]
    public function dismiss(JsonRpcRequest $request): array
    {
        $params = $request->validate([
            'event_id' => 'required|integer|exists:ynh_osquery,id',
        ]);

        /** @var YnhOsquery $event */
        $event = YnhOsquery::find($params['event_id']);
        /** @var YnhServer $server */
        $server = YnhServer::find($event->ynh_server_id);

        if (!$server) {
            throw new \Exception("Unknown server.");
        }

        $event->dismissed = true;
        $event->save();

        return [
            "msg" => "The event has been dismissed!",
        ];
    }
}