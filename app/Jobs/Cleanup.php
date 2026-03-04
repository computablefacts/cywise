<?php

namespace App\Jobs;

use App\Models\Asset;
use App\Models\Collection;
use App\Models\Conversation;
use App\Models\File;
use App\Models\ScheduledTask;
use App\Models\Tenant;
use App\Models\TimelineFact;
use App\Models\TimelineItem;
use App\Models\Trial;
use App\Models\User;
use App\Models\Vector;
use App\Models\YnhFramework;
use App\Models\YnhOsquery;
use App\Models\YnhOsqueryLatestEvent;
use App\Models\YnhOsqueryRule;
use App\Models\YnhServer;
use App\Notifications\Notification;
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

    const int DELETION_DELAY_DAYS = 3;

    public $tries = 1;
    public $maxExceptions = 1;
    public $timeout = 3 * 180; // 9mn

    public function __construct()
    {
        //
    }

    public function handle()
    {
        Log::debug("Cleaning up non-paying customers...");

        $this->cleanupTenants();

        Log::debug("Non-paying customers cleaned.");
        Log::debug("Cleaning up trials...");

        Trial::whereNull('created_by')
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
                ->where(function ($query) {
                    $query->where(function ($q) {
                        $q->whereNotNull('collection_id')->whereDoesntHave('collection');
                    })->orWhere(function ($q) {
                        $q->whereNotNull('file_id')->whereDoesntHave('file');
                    })->orWhere(function ($q) {
                        $q->whereNotNull('chunk_id')->whereDoesntHave('chunk');
                    });
                })
                ->orderBy('id')
                ->chunkById(50, function (\Illuminate\Support\Collection $vectors) {
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

    private function cleanupTenants()
    {
        Tenant::where('cleanup', true)
            /* ->where(function ($query) {
                $query->whereNull('deletion_scheduled_at')
                    ->orWhere('deletion_scheduled_at', '<=', now());
            }) */
            ->get()
            ->each(function (Tenant $tenant) {

                $users = User::withoutGlobalScope('tenant_scope')->where('tenant_id', $tenant->id)->get();
                $hasPayingUser = $users->contains(fn(User $user) => $user->subscriber());

                if ($hasPayingUser) {
                    if ($tenant->deletion_scheduled_at !== null) {

                        Log::debug("Tenant {$tenant->id} has a paying user now. Cancelling deletion.");

                        $tenant->deletion_scheduled_at = null;
                        $tenant->save();
                    }
                    return;
                }

                // No paying user
                if ($tenant->deletion_scheduled_at === null) {
                    if ($this->hasDataToCleanup($users)) {

                        Log::debug("Tenant {$tenant->id} has no paying user but has data. Scheduling deletion in " . self::DELETION_DELAY_DAYS . " days.");

                        $tenant->deletion_scheduled_at = now()->addDays(self::DELETION_DELAY_DAYS)->endOfDay();
                        $tenant->save();

                        $users->each(function (User $user) use ($tenant) {
                            $terms = "https://www.cywise.io/terms";
                            $delay = self::DELETION_DELAY_DAYS;
                            $user->notify(new Notification("
                              <p>Bonjour,</p>
                              <p>Votre p&eacute;riode d'essai sur Cywise arrive &agrave; son terme. Conform&eacute;ment &agrave; nos <a href=\"{$terms}\">conditions d'utilisation</a>, <b>votre compte sera d&eacute;sactiv&eacute; et les donn&eacute;es associ&eacute;es seront supprim&eacute;es dans {$delay} jours, soit le {$tenant->deletion_scheduled_at->format('Y-m-d')}.</b></p>
                              <p>Si vous souhaitez prolonger votre exp&eacute;rience ou discuter d'une solution adapt&eacute;e &agrave; vos besoins, n'h&eacute;sitez pas &agrave; r&eacute;pondre &agrave; cet email.</p>
                              <p>Nous restons &agrave; votre disposition pour toute question.</p>
                              <p>Bonne journ&eacute;e !</p>
                            ", "📢 Fin de votre période d'essai sur Cywise"));
                        });
                    }
                } else if ($tenant->deletion_scheduled_at <= now()) {

                    Log::info("Tenant {$tenant->id} deletion delay expired. Cleaning up data.");

                    $this->cleanupTenantData($users);

                    $tenant->deletion_scheduled_at = null;
                    $tenant->save();

                    $users->each(function (User $user) {
                        $user->notify(new Notification("
                            <p>Bonjour,</p>
                            <p>Conform&eacute;ment &agrave; ce qui vous a &eacute;t&eacute; annonc&eacute;, vos donn&eacute;es ont maintenant &eacute;t&eacute; supprim&eacute;es. Cependant, votre compte utilisateur reste actif.</p>
                            <p>Nous restons &agrave; votre disposition pour toute question.</p>
                            <p>Bonne journ&eacute;e !</p>
                        ", "📢 Confirmation de la suppression de vos données sur Cywise"));
                    });
                }
            });
    }

    private function hasDataToCleanup(\Illuminate\Support\Collection $users): bool
    {
        $userIds = $users->pluck('id')->toArray();

        if (empty($userIds)) {
            return false;
        }
        return Asset::withoutGlobalScope('tenant_scope')->whereIn('created_by', $userIds)->exists()
            || YnhServer::withoutGlobalScope('tenant_scope')->whereIn('created_by', $userIds)->exists()
            || ScheduledTask::withoutGlobalScope('tenant_scope')->whereIn('created_by', $userIds)->exists()
            || Conversation::withoutGlobalScope('tenant_scope')->whereIn('created_by', $userIds)->exists()
            || File::withoutGlobalScope('tenant_scope')->whereIn('created_by', $userIds)->exists()
            || Collection::withoutGlobalScope('tenant_scope')->whereIn('created_by', $userIds)->exists()
            || Trial::withoutGlobalScope('tenant_scope')->whereIn('created_by', $userIds)->exists()
            || Vector::withoutGlobalScope('tenant_scope')->whereIn('created_by', $userIds)->exists()
            || TimelineItem::whereIn('owned_by', $userIds)->exists()
            || TimelineFact::whereIn('owned_by', $userIds)->exists();
    }

    private function cleanupTenantData(\Illuminate\Support\Collection $users)
    {
        $userIds = $users->pluck('id')->toArray();

        if (empty($userIds)) {
            return;
        }

        // 1. Assets and related (Scans, Ports, Alerts, Screenshots, AssetTags)
        $assetIds = Asset::withoutGlobalScope('tenant_scope')->whereIn('created_by', $userIds)->pluck('id')->toArray();

        if (!empty($assetIds)) {

            $scanIds = DB::table('am_scans')->whereIn('asset_id', $assetIds)->pluck('id')->toArray();

            if (!empty($scanIds)) {

                $portIds = DB::table('am_ports')->whereIn('scan_id', $scanIds)->pluck('id')->toArray();

                if (!empty($portIds)) {
                    DB::table('am_screenshots')->whereIn('port_id', $portIds)->delete();
                    DB::table('am_alerts')->whereIn('port_id', $portIds)->delete();
                    DB::table('am_ports_tags')->whereIn('port_id', $portIds)->delete();
                    DB::table('am_ports')->whereIn('id', $portIds)->delete();
                }

                DB::table('am_scans')->whereIn('id', $scanIds)->delete();
            }

            DB::table('am_assets_tags')->whereIn('asset_id', $assetIds)->delete();
            Asset::withoutGlobalScope('tenant_scope')->whereIn('id', $assetIds)->delete();
        }

        // 2. Servers and related (Osquery)
        $serverIds = YnhServer::withoutGlobalScope('tenant_scope')->whereIn('created_by', $userIds)->pluck('id')->toArray();

        if (!empty($serverIds)) {
            YnhOsqueryLatestEvent::whereIn('ynh_server_id', $serverIds)->delete();
            YnhOsquery::whereIn('ynh_server_id', $serverIds)->delete();
            YnhServer::withoutGlobalScope('tenant_scope')->whereIn('id', $serverIds)->delete();
        }

        // 3. ScheduledTasks, Conversations, Vectors, Files, Collections, Trials, etc.
        ScheduledTask::withoutGlobalScope('tenant_scope')->whereIn('created_by', $userIds)->delete();
        Conversation::withoutGlobalScope('tenant_scope')->whereIn('created_by', $userIds)->delete();
        Vector::withoutGlobalScope('tenant_scope')->whereIn('created_by', $userIds)->delete();
        File::withoutGlobalScope('tenant_scope')->whereIn('created_by', $userIds)->delete();
        Collection::withoutGlobalScope('tenant_scope')->whereIn('created_by', $userIds)->delete();
        Trial::withoutGlobalScope('tenant_scope')->whereIn('created_by', $userIds)->delete();

        // 4. Leaks & co
        TimelineItem::whereIn('owned_by', $userIds)->delete();
        TimelineFact::whereIn('owned_by', $userIds)->delete();
    }
}
