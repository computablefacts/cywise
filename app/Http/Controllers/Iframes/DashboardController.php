<?php

namespace App\Http\Controllers\Iframes;

use App\Http\Controllers\Controller;
use App\Http\Procedures\AssetsProcedure;
use App\Http\Procedures\EventsProcedure;
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

        $counts = $procedure->counts(JsonRpcRequest::createFrom($request));
        $nbMonitored = $counts['monitored'];
        $nbMonitorable = $counts['monitorable'];

        $procedure = new EventsProcedure();

        $counts = $procedure->counts(JsonRpcRequest::createFrom($request));
        $nbIocsHigh = $counts['high'];
        $nbIocsMedium = $counts['medium'];
        $nbIocsLow = $counts['low'];

        $procedure = new VulnerabilitiesProcedure();

        $counts = $procedure->counts(JsonRpcRequest::createFrom($request));
        $nbVulnsHigh = $counts['high'];
        $nbVulnsMedium = $counts['medium'];
        $nbVulnsLow = $counts['low'];

        $req = JsonRpcRequest::createFrom($request);
        $req->merge(['level' => 'high']);
        $alerts = $procedure->list($req)['high'];

        if ($alerts->count() < 5) {
            $req->merge(['level' => 'medium']);
            $alerts = $alerts->concat($procedure->list($req)['medium']);
        }
        if ($alerts->count() < 5) {
            $req->merge(['level' => 'low']);
            $alerts = $alerts->concat($procedure->list($req)['low']);
        }

        $leaks = TimelineController::fetchLeaks($request->user())
            ->flatMap(fn(TimelineItem $item) => json_decode($item->attributes()['credentials']))
            ->sortBy('leak_date', SORT_NATURAL | SORT_FLAG_CASE)
            ->reverse()
            ->take(10);

        $todo = $alerts->sortBy(function (Alert $alert) {
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
            'nb_vulns_high' => $nbVulnsHigh,
            'nb_vulns_medium' => $nbVulnsMedium,
            'nb_vulns_low' => $nbVulnsLow,
            'nb_iocs_high' => $nbIocsHigh,
            'nb_iocs_medium' => $nbIocsMedium,
            'nb_iocs_low' => $nbIocsLow,
            'todo' => $todo,
            'leaks' => $leaks,
            'honeypots' => $honeypots,
            'most_recent_honeypot_events' => $mostRecentHoneypotEvents,
        ]);
    }
}
