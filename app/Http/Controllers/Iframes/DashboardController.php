<?php

namespace App\Http\Controllers\Iframes;

use App\Http\Controllers\Controller;
use App\Http\Procedures\AssetsProcedure;
use App\Http\Procedures\VulnerabilitiesProcedure;
use App\Models\Alert;
use App\Models\Honeypot;
use App\Models\HoneypotEvent;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $procedure = new AssetsProcedure();

        $request->replace(['is_monitored' => true]);
        $nbMonitored = count($procedure->list($request)['assets'] ?? []);

        $request->replace(['is_monitored' => false]);
        $nbMonitorable = count($procedure->list($request)['assets'] ?? []);

        $procedure = new VulnerabilitiesProcedure();

        $alerts = $procedure->list($request);
        $nbHigh = count($alerts['high'] ?? []);
        $nbMedium = count($alerts['medium'] ?? []);
        $nbLow = count($alerts['low'] ?? []);
        
        $todo = collect($alerts['high'] ?? [])
            ->concat($alerts['medium'] ?? [])
            ->concat($alerts['low'] ?? [])
            ->sortBy(function (Alert $alert) {
                if ($alert->level === 'High') {
                    return 1;
                }
                if ($alert->level === 'Medium') {
                    return 2;
                }
                if ($alert->level === 'Low') {
                    return 3;
                }
                return 4;
            })
            ->values()
            ->take(5);

        $honeypots = Honeypot::all()
            ->map(function (Honeypot $honeypot) {
                $counts = $this->honeypotEventCounts($honeypot);
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
                $events = $this->mostRecentHoneypotEvents($honeypot);
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
            'honeypots' => $honeypots,
            'most_recent_honeypot_events' => $mostRecentHoneypotEvents,
        ]);
    }

    private function honeypotEventCounts(Honeypot $honeypot): array
    {
        $cutOffTime = Carbon::now()->startOfDay()->subMonth();
        return HoneypotEvent::select(
            DB::raw("DATE_FORMAT(timestamp, '%Y-%m-%d') AS date"),
            DB::raw("SUM(CASE WHEN human = 1 OR targeted = 1 THEN 1 ELSE 0 END) AS human_or_targeted"),
            DB::raw("SUM(CASE WHEN human = 0 AND targeted = 0 THEN 1 ELSE 0 END) AS not_human_or_targeted")
        )
            ->where('timestamp', '>=', $cutOffTime)
            ->where('honeypot_id', $honeypot->id)
            ->groupBy('date')
            ->orderBy('date', 'desc') // keep only the most recent ones
            ->limit(10)
            ->get()
            ->sortBy('date') // most recent date at the end
            ->toArray();
    }

    private function mostRecentHoneypotEvents(Honeypot $honeypot): array
    {
        /** @var array $ips */
        $ips = config('towerify.adversarymeter.ip_addresses');
        return HoneypotEvent::select(
            'am_honeypots_events.*',
            DB::raw("CASE WHEN am_attackers.name IS NULL THEN '-' ELSE am_attackers.name END AS internal_name"),
            DB::raw("CASE WHEN am_attackers.id IS NULL THEN '-' ELSE am_attackers.id END AS attacker_id"),
        )
            ->where('honeypot_id', $honeypot->id)
            ->whereNotIn('ip', $ips)
            ->leftJoin('am_attackers', 'am_attackers.id', '=', 'am_honeypots_events.attacker_id')
            ->orderBy('timestamp', 'desc')
            ->limit(5)
            ->get()
            ->map(function (HoneypotEvent $event) {
                return [
                    'timestamp' => $event->timestamp->utc()->format('Y-m-d H:i:s'),
                    'event_type' => $event->event,
                    'event_details' => $event->details,
                    'attacker_ip' => $event->ip,
                    'attacker_name' => $event->internal_name,
                    'attacker_id' => $event->attacker_id,
                ];
            })
            ->toArray();
    }
}
