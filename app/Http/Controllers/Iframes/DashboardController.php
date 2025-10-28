<?php

namespace App\Http\Controllers\Iframes;

use App\Http\Controllers\Controller;
use App\Http\Procedures\AssetsProcedure;
use App\Http\Procedures\HoneypotsProcedure;
use App\Http\Procedures\VulnerabilitiesProcedure;
use App\Http\Requests\JsonRpcRequest;
use App\Models\Alert;
use App\Models\Honeypot;
use App\Models\TimelineItem;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $procedure = new AssetsProcedure();

        $request->replace(['is_monitored' => true]);
        $nbMonitored = count($procedure->list(JsonRpcRequest::createFrom($request))['assets'] ?? []);

        $request->replace(['is_monitored' => false]);
        $nbMonitorable = count($procedure->list(JsonRpcRequest::createFrom($request))['assets'] ?? []);

        $procedure = new VulnerabilitiesProcedure();

        $alerts = $procedure->list(JsonRpcRequest::createFrom($request));
        $nbHigh = count($alerts['high'] ?? []);
        $nbMedium = count($alerts['medium'] ?? []);
        $nbLow = count($alerts['low'] ?? []);

        $leaks = TimelineController::fetchLeaks(\Auth::user())
            ->flatMap(fn(TimelineItem $item) => json_decode($item->attributes()['credentials']))
            ->sortBy('leak_date', SORT_NATURAL | SORT_FLAG_CASE)
            ->reverse()
            ->take(10);

        $todo = collect($alerts['high'] ?? [])
            ->concat($alerts['medium'] ?? [])
            ->concat($alerts['low'] ?? [])
            ->sortBy(function (Alert $alert) {
                if ($alert->isCritical()) {
                    return 0;
                }
                if ($alert->isHigh()) {
                    return 1;
                }
                if ($alert->isMedium()) {
                    return 2;
                }
                if ($alert->isLow()) {
                    return 3;
                }
                return 4;
            })
            ->values()
            ->take(5);

        $honeypots = Honeypot::all()
            ->map(function (Honeypot $honeypot) {
                $request = new JsonRpcRequest(['honeypot_id' => $honeypot->id]);
                $request->setUserResolver(fn() => \Auth::user());
                $counts = (new HoneypotsProcedure())->counts($request)['counts'];
                $max = collect($counts)->max(fn($count) => $count['human_or_targeted'] + $count['not_human_or_targeted']);
                $sum = collect($counts)->sum(fn($count) => $count['human_or_targeted'] + $count['not_human_or_targeted']);
                return [
                    'name' => $honeypot->dns,
                    'type' => $honeypot->cloud_sensor,
                    'counts' => $counts,
                    'max' => $max,
                    'sum' => $sum,
                ];
            })
            ->sortBy(fn(array $honeypot) => [-$honeypot['sum'], $honeypot['name']])
            ->values()
            ->take(3)
            ->toArray();

        $mostRecentHoneypotEvents = Honeypot::all()
            ->map(function (Honeypot $honeypot) {
                $request = new JsonRpcRequest([
                    'honeypot_id' => $honeypot->id,
                    'limit' => 5,
                ]);
                $request->setUserResolver(fn() => \Auth::user());
                $events = (new HoneypotsProcedure())->events($request)['events'];
                return [
                    'name' => $honeypot->dns,
                    'events' => $events,
                ];
            })
            ->groupBy('name')
            ->map(fn($group) => $group->first())
            ->take(3)
            ->toArray();

        return view('theme::iframes.dashboard', [
            'nb_monitored' => $nbMonitored,
            'nb_monitorable' => $nbMonitorable,
            'nb_high' => $nbHigh,
            'nb_medium' => $nbMedium,
            'nb_low' => $nbLow,
            'todo' => $todo,
            'leaks' => $leaks,
            'honeypots' => $honeypots,
            'most_recent_honeypot_events' => $mostRecentHoneypotEvents,
        ]);
    }
}
