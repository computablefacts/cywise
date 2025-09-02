<?php

namespace App\Jobs;

use App\Models\Collection;
use App\Models\User;
use App\Models\YnhFramework;
use App\Models\YnhOsquery;
use App\Models\YnhOsqueryLatestEvent;
use App\Models\YnhOsqueryRule;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Cleanup implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    public $maxExceptions = 1;
    public $timeout = 3 * 180; // 9mn

    public function __construct()
    {
        //
    }

    public function handle()
    {
        Log::debug('Cleanup begins');

        // When a rule is disabled, cleanup the history
        $rules = YnhOsqueryRule::where('enabled', false)->get()->pluck('name');
        YnhOsquery::whereIn('name', $rules)->limit(10000)->delete();
        YnhOsqueryLatestEvent::whereIn('event_name', $rules)->delete();

        Log::debug('Cleanup completed');

        // When the list of cached events "overflow" for a given (server, rule), remove the oldest events
        $threshold = 1000;

        $overflowingEvents = DB::table('ynh_osquery_latest_events')
            ->select('ynh_server_id', 'server_name', 'event_name', DB::raw('COUNT(*) as event_count'))
            ->whereNotIn('event_name', $rules)
            ->groupBy('ynh_server_id', 'server_name', 'event_name')
            ->having('event_count', '>', $threshold)
            ->get();

        Log::debug("Found {$overflowingEvents->count()} overflowing events");

        foreach ($overflowingEvents as $event) {
            Log::debug("Compacting {$event->event_name} for {$event->server_name}");
            DB::table('ynh_osquery_latest_events')
                ->where('ynh_server_id', $event->ynh_server_id)
                ->where('event_name', $event->event_name)
                ->orderBy('calendar_time')
                ->limit($event->event_count - $threshold)
                ->delete();
        }

        Log::debug('Remove empty framework collections');

        User::all()->each(function (User $user) {

            Auth::login($user);

            YnhFramework::all()->each(function (YnhFramework $framework) {

                $collectionName = $framework->collectionName();

                Collection::query()
                    ->where('name', $collectionName)
                    ->where('is_deleted', false)
                    ->where('created_at', '<', now()->subDays(7))
                    ->get()
                    ->filter(fn(Collection $collection) => $collection->files()->exists())
                    ->each(function (Collection $collection) {
                        Log::debug("Marking collection {$collection->name} as deleted");
                        $collection->is_deleted = true;
                        $collection->save();
                    });
            });
            
            Auth::logout();
        });
    }
}
