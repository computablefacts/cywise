<?php

namespace App\Jobs;

use App\Models\Collection;
use App\Models\Conversation;
use App\Models\User;
use App\Models\Vector;
use App\Models\YnhFramework;
use App\Models\YnhOsquery;
use App\Models\YnhOsqueryLatestEvent;
use App\Models\YnhOsqueryRule;
use App\Models\YnhTrial;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
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
        Log::debug("Cleaning up trials...");

        YnhTrial::whereNull('created_by')
            ->where('updated_at', '<', now()->subDays(10))
            ->delete();

        Log::debug("Trials cleaned up.");
        Log::debug("Removing events associated to disabled osquery rules...");

        // When a rule is disabled, cleanup the history
        $rules = YnhOsqueryRule::where('enabled', false)->get()->pluck('name');
        YnhOsquery::whereIn('name', $rules)->limit(10000)->delete();
        YnhOsqueryLatestEvent::whereIn('event_name', $rules)->delete();

        Log::debug("Events removed.");
        Log::debug("Finding overflowing events...");

        // When the list of cached events "overflow" for a given (server, rule), remove the oldest events
        $threshold = 1000;

        $overflowingEvents = DB::table('ynh_osquery_latest_events')
            ->select('ynh_server_id', 'server_name', 'event_name', DB::raw('COUNT(*) as event_count'))
            ->whereNotIn('event_name', $rules)
            ->groupBy('ynh_server_id', 'server_name', 'event_name')
            ->having('event_count', '>', $threshold)
            ->get();

        Log::debug("{$overflowingEvents->count()} overflowing events found.");
        Log::debug("Removing overflowing events...");

        foreach ($overflowingEvents as $event) {
            Log::debug("Compacting events {$event->event_name} for server {$event->server_name}...");
            DB::table('ynh_osquery_latest_events')
                ->where('ynh_server_id', $event->ynh_server_id)
                ->where('event_name', $event->event_name)
                ->orderBy('calendar_time')
                ->limit($event->event_count - $threshold)
                ->delete();
            Log::debug("Events {$event->event_name} for server {$event->server_name} compacted.");
        }

        Log::debug("Overflowing events removed.");

        User::all()->each(function (User $user) {

            $user->actAs(); // otherwise the tenant will not be properly set

            Log::debug("Removing empty framework collections for user {$user->email}...");

            YnhFramework::all()->each(function (YnhFramework $framework) {

                $collectionName = $framework->collectionName();

                Collection::query()
                    ->where('name', $collectionName)
                    ->where('is_deleted', false)
                    ->where('created_at', '<', now()->subDays(7))
                    ->get()
                    ->filter(fn(Collection $collection) => !$collection->files()->exists())
                    ->each(function (Collection $collection) {
                        Log::debug("Marking collection {$collection->name} as deleted...");
                        $collection->is_deleted = true;
                        $collection->save();
                        Log::debug("Collection {$collection->name} marked as deleted.");
                    });
            });

            Log::debug("Empty framework collections for user {$user->email} removed.");
            Log::debug("Removing vectors with missing references for user {$user->email}...");

            Vector::query()
                ->where('created_by', $user->id)
                ->orderBy('id')
                ->chunkById(25, function (\Illuminate\Support\Collection $vectors) {
                    Log::debug("Processing chunk of {$vectors->count()} vectors...");
                    $vectors->each(function (Vector $vector) {

                        $hasCollection = true;
                        $hasFile = true;
                        $hasChunk = true;

                        if (!$vector->collection()?->exists()) {
                            $vector->collection_id = null;
                            $hasCollection = false;
                            Log::debug("Vector {$vector->id} has no collection.");
                        }
                        if (!$vector->file()?->exists()) {
                            $vector->file_id = null;
                            $hasFile = false;
                            Log::debug("Vector {$vector->id} has no file.");
                        }
                        if (!$vector->chunk()?->exists()) {
                            $vector->chunk_id = null;
                            $hasChunk = false;
                            Log::debug("Vector {$vector->id} has no chunk.");
                        }
                        if (!$hasCollection && !$hasFile && !$hasChunk) {
                            $vector->delete();
                            Log::debug("Vector {$vector->id} removed.");
                        } else if (!$hasCollection || !$hasFile || !$hasChunk) {
                            $vector->save();
                            Log::debug("Vector {$vector->id} updated.");
                        }
                    });
                    Log::debug("Chunk of {$vectors->count()} vectors processed.");
                });

            Log::debug("Vectors with missing references for user {$user->email} removed.");
            Log::debug("Purging conversations of user {$user->email} that are over 6 months old...");

            Conversation::where('updated_at', '<=', Carbon::now()->startOfDay()->subMonths(6))
                ->delete();

            Log::debug("Conversations of user {$user->email} purged.");
        });
    }
}
