<?php

namespace App\Console\Commands;

use App\Models\Role;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Wave\Plan;
use Wave\Subscription;

class CopyDataFromOldDbToNewDb extends Command
{
    protected $signature = 'db:from-old-to-new';
    protected $description = 'Copy data from the old database to the new one';

    public function handle()
    {
        DB::transaction(function () {

            Schema::disableForeignKeyConstraints();

            try {

                Log::info('tenants...');

                \DB::connection('mysql_legacy')
                    ->table('tenants')
                    ->chunkById(100, fn(Collection $items) => $this->upsertAll('tenants', $items));

                Log::info('users...');

                $usernameCounts = \DB::connection('mysql_legacy')
                    ->table('users')
                    ->select(\DB::raw('LOWER(name) as name_lower'), \DB::raw('COUNT(*) as count'))
                    ->groupBy('name_lower')
                    ->get()
                    ->pluck('count', 'name_lower')
                    ->toArray();

                Log::info($usernameCounts);

                \DB::connection('mysql_legacy')
                    ->table('users')
                    ->chunkById(100, function ($items) use (&$usernameCounts) {
                        /** @var object $item */
                        foreach ($items as $item) {
                            $count = --$usernameCounts[Str::lower($item->name)];
                            $item->username = $count === 0 ? $item->name : ($item->name . $count);
                            $item->password = ($item->password === '<deleted>') ? '<deleted>' : Hash::make(cywise_unhash($item->password));
                            $item->ynh_password = $item->password;
                            $item->verified = true;
                            $item->avatar = 'demo/default.png';
                            $this->upsert('users', $item);
                        }
                    });

                Log::info('roles...');

                \DB::connection('mysql_legacy')
                    ->table('roles')
                    ->chunkById(100, fn(Collection $items) => $this->upsertAll('roles', $items));

                Log::info('permissions...');

                \DB::connection('mysql_legacy')
                    ->table('permissions')
                    ->chunkById(100, fn(Collection $items) => $this->upsertAll('permissions', $items));

                Log::info('model_has_roles...');

                \DB::connection('mysql_legacy')
                    ->table('model_roles')
                    ->get()
                    ->each(function (object $item) {
                        $item->model_type = 'users';
                        $objKeys = array_keys((array)$item);
                        $tblKeys = Schema::getColumnListing('model_has_roles');
                        $keys = array_intersect($objKeys, $tblKeys);
                        $newItem = array_intersect_key((array)$item, array_flip($tblKeys));
                        \DB::table('model_has_roles')->upsert($newItem, $tblKeys, $keys);
                    });

                Log::info('model_has_permissions...');

                \DB::connection('mysql_legacy')
                    ->table('model_permissions')
                    ->get()
                    ->each(function (object $item) {
                        $item->model_type = 'users';
                        $objKeys = array_keys((array)$item);
                        $tblKeys = Schema::getColumnListing('model_has_permissions');
                        $keys = array_intersect($objKeys, $tblKeys);
                        $newItem = array_intersect_key((array)$item, array_flip($tblKeys));
                        \DB::table('model_has_permissions')->upsert($newItem, $tblKeys, $keys);
                    });

                Log::info('role_has_permissions...');

                \DB::connection('mysql_legacy')
                    ->table('role_permissions')
                    ->get()
                    ->each(function (object $item) {
                        $item->model_type = 'users';
                        $objKeys = array_keys((array)$item);
                        $tblKeys = Schema::getColumnListing('role_has_permissions');
                        $keys = array_intersect($objKeys, $tblKeys);
                        $newItem = array_intersect_key((array)$item, array_flip($tblKeys));
                        \DB::table('role_has_permissions')->upsert($newItem, $tblKeys, $keys);
                    });

                Log::info('am_alerts...');

                \DB::connection('mysql_legacy')
                    ->table('am_alerts')
                    ->chunkById(100, fn(Collection $items) => $this->upsertAll('am_alerts', $items));

                Log::info('am_assets...');

                \DB::connection('mysql_legacy')
                    ->table('am_assets')
                    ->chunkById(100, fn(Collection $items) => $this->upsertAll('am_assets', $items));

                Log::info('am_assets_tags...');

                \DB::connection('mysql_legacy')
                    ->table('am_assets_tags')
                    ->chunkById(100, fn(Collection $items) => $this->upsertAll('am_assets_tags', $items));

                Log::info('am_assets_tags_hashes...');

                \DB::connection('mysql_legacy')
                    ->table('am_assets_tags_hashes')
                    ->chunkById(100, fn(Collection $items) => $this->upsertAll('am_assets_tags_hashes', $items));

                Log::info('am_attackers...');

                \DB::connection('mysql_legacy')
                    ->table('am_attackers')
                    ->chunkById(100, fn(Collection $items) => $this->upsertAll('am_attackers', $items));

                Log::info('am_hidden_alerts...');

                \DB::connection('mysql_legacy')
                    ->table('am_hidden_alerts')
                    ->chunkById(100, fn(Collection $items) => $this->upsertAll('am_hidden_alerts', $items));

                Log::info('am_honeypots...');

                \DB::connection('mysql_legacy')
                    ->table('am_honeypots')
                    ->chunkById(100, fn(Collection $items) => $this->upsertAll('am_honeypots', $items));

                Log::info('am_honeypots_events...');

                \DB::connection('mysql_legacy')
                    ->table('am_honeypots_events')
                    ->orderBy('updated_at', 'desc')
                    ->limit(1000)
                    ->chunkById(100, fn(Collection $items) => $this->upsertAll('am_honeypots_events', $items));

                Log::info('am_ports...');

                \DB::connection('mysql_legacy')
                    ->table('am_ports')
                    ->chunkById(100, fn(Collection $items) => $this->upsertAll('am_ports', $items));

                Log::info('am_ports_tags...');

                \DB::connection('mysql_legacy')
                    ->table('am_ports_tags')
                    ->chunkById(100, fn(Collection $items) => $this->upsertAll('am_ports_tags', $items));

                Log::info('am_scans...');

                \DB::connection('mysql_legacy')
                    ->table('am_scans')
                    ->chunkById(100, fn(Collection $items) => $this->upsertAll('am_scans', $items));

                Log::info('am_screenshots...');

                \DB::connection('mysql_legacy')
                    ->table('am_screenshots')
                    ->chunkById(100, fn(Collection $items) => $this->upsertAll('am_screenshots', $items));

                Log::info('cb_chunks...');

                \DB::connection('mysql_legacy')
                    ->table('cb_chunks')
                    ->chunkById(100, fn(Collection $items) => $this->upsertAll('cb_chunks', $items));

                Log::info('cb_chunks_tags...');

                \DB::connection('mysql_legacy')
                    ->table('cb_chunks_tags')
                    ->chunkById(100, fn(Collection $items) => $this->upsertAll('cb_chunks_tags', $items));

                Log::info('cb_collections...');

                \DB::connection('mysql_legacy')
                    ->table('cb_collections')
                    ->chunkById(100, fn(Collection $items) => $this->upsertAll('cb_collections', $items));

                Log::info('cb_conversations...');

                \DB::connection('mysql_legacy')
                    ->table('cb_conversations')
                    ->chunkById(100, fn(Collection $items) => $this->upsertAll('cb_conversations', $items));

                Log::info('cb_files...');

                \DB::connection('mysql_legacy')
                    ->table('cb_files')
                    ->chunkById(100, fn(Collection $items) => $this->upsertAll('cb_files', $items));

                Log::info('cb_prompts...');

                \DB::connection('mysql_legacy')
                    ->table('cb_prompts')
                    ->chunkById(100, fn(Collection $items) => $this->upsertAll('cb_prompts', $items));

                Log::info('cb_scheduled_tasks...');

                \DB::connection('mysql_legacy')
                    ->table('cb_scheduled_tasks')
                    ->chunkById(100, fn(Collection $items) => $this->upsertAll('cb_scheduled_tasks', $items));

                Log::info('cb_tables...');

                \DB::connection('mysql_legacy')
                    ->table('cb_tables')
                    ->chunkById(100, fn(Collection $items) => $this->upsertAll('cb_tables', $items));

                Log::info('cb_templates...');

                \DB::connection('mysql_legacy')
                    ->table('cb_templates')
                    ->chunkById(100, fn(Collection $items) => $this->upsertAll('cb_templates', $items));

                Log::info('health_check_result_history_items...');

                \DB::connection('mysql_legacy')
                    ->table('health_check_result_history_items')
                    ->orderBy('created_at', 'desc')
                    ->limit(1000)
                    ->chunkById(100, fn(Collection $items) => $this->upsertAll('health_check_result_history_items', $items));

                Log::info('personal_access_tokens...');

                \DB::connection('mysql_legacy')
                    ->table('personal_access_tokens')
                    ->chunkById(100, function ($items) {
                        /** @var object $item */
                        foreach ($items as $item) {
                            $item->tokenable_type = 'App\Models\User';
                            $this->upsert('personal_access_tokens', $item);
                        }
                    });

                Log::info('saml2_tenants...');

                \DB::connection('mysql_legacy')
                    ->table('saml2_tenants')
                    ->chunkById(100, fn(Collection $items) => $this->upsertAll('saml2_tenants', $items));

                Log::info('create plans...');

                Plan::updateOrCreate([
                    'name' => 'Essential',
                ], [
                    'description' => '',
                    'features' => '',
                    'role_id' => Role::where('name', 'administrator')->where('guard_name', 'web')->firstOrFail()->id,
                    'default' => 0,
                    'monthly_price' => '150',
                    'monthly_price_id' => config('towerify.stripe.plans.essential.monthly_price_id'),
                ]);
                Plan::updateOrCreate([
                    'name' => 'Standard',
                ], [
                    'description' => '',
                    'features' => '',
                    'role_id' => Role::where('name', 'administrator')->where('guard_name', 'web')->firstOrFail()->id,
                    'default' => 1,
                    'monthly_price' => '400',
                    'monthly_price_id' => config('towerify.stripe.plans.standard.monthly_price_id'),
                ]);
                Plan::updateOrCreate([
                    'name' => 'Premium',
                ], [
                    'description' => '',
                    'features' => '',
                    'role_id' => Role::where('name', 'administrator')->where('guard_name', 'web')->firstOrFail()->id,
                    'default' => 0,
                    'monthly_price' => '600',
                    'monthly_price_id' => config('towerify.stripe.plans.premium.monthly_price_id'),
                ]);

                Log::info('subscriptions...');

                \DB::connection('mysql_legacy')
                    ->table('subscriptions')
                    ->get()
                    ->each(function (object $item) {
                        $user = \DB::connection('mysql_legacy')
                            ->table('users')
                            ->where('id', $item->user_id)
                            ->first();

                        $plan = Plan::query()
                            ->where('monthly_price_id', $item->stripe_price)
                            ->first();
                        if (is_null($plan)) {
                            // Default to Essential plan
                            $plan = Plan::query()
                                ->where('name', '=', 'Essential')
                                ->firstOrFail();
                        }

                        Subscription::query()->updateOrCreate([
                            'billable_type' => 'user',
                            'billable_id' => $item->user_id,
                        ], [
                            'plan_id' => $plan->id,
                            'vendor_slug' => 'stripe',
                            'vendor_customer_id' => $user->stripe_id,
                            'vendor_subscription_id' => $item->stripe_id,
                            'status' => $item->stripe_status,
                            'cycle' => 'month',
                            'seats' => 1,
                        ]);
                    });

                Log::info('t_facts...');

                \DB::connection('mysql_legacy')
                    ->table('t_facts')
                    ->chunkById(100, fn(Collection $items) => $this->upsertAll('t_facts', $items));

                Log::info('t_facts_items...');

                \DB::connection('mysql_legacy')
                    ->table('t_facts_items')
                    ->chunkById(100, fn(Collection $items) => $this->upsertAll('t_facts_items', $items));

                Log::info('t_items...');

                \DB::connection('mysql_legacy')
                    ->table('t_items')
                    ->chunkById(100, fn(Collection $items) => $this->upsertAll('t_items', $items));

                Log::info('t_items_items...');

                \DB::connection('mysql_legacy')
                    ->table('t_items_items')
                    ->chunkById(100, fn(Collection $items) => $this->upsertAll('t_items_items', $items));

                Log::info('tcb_stories...');

                \DB::connection('mysql_legacy')
                    ->table('tcb_stories')
                    ->chunkById(100, fn(Collection $items) => $this->upsertAll('tcb_stories', $items));

                Log::info('ynh_applications...');

                \DB::connection('mysql_legacy')
                    ->table('ynh_applications')
                    ->chunkById(100, fn(Collection $items) => $this->upsertAll('ynh_applications', $items));

                Log::info('ynh_backups...');

                \DB::connection('mysql_legacy')
                    ->table('ynh_backups')
                    ->chunkById(100, fn(Collection $items) => $this->upsertAll('ynh_backups', $items));

                Log::info('ynh_cves...');

                \DB::connection('mysql_legacy')
                    ->table('ynh_cves')
                    ->chunkById(100, fn(Collection $items) => $this->upsertAll('ynh_cves', $items));

                Log::info('ynh_domains...');

                \DB::connection('mysql_legacy')
                    ->table('ynh_domains')
                    ->chunkById(100, fn(Collection $items) => $this->upsertAll('ynh_domains', $items));

                Log::info('ynh_frameworks...');

                \DB::connection('mysql_legacy')
                    ->table('ynh_frameworks')
                    ->chunkById(100, fn(Collection $items) => $this->upsertAll('ynh_frameworks', $items));

                Log::info('ynh_mitre_attck...');

                \DB::connection('mysql_legacy')
                    ->table('ynh_mitre_attck')
                    ->chunkById(100, fn(Collection $items) => $this->upsertAll('ynh_mitre_attck', $items));

                Log::info('ynh_nginx_logs...');

                \DB::connection('mysql_legacy')
                    ->table('ynh_nginx_logs')
                    ->chunkById(100, fn(Collection $items) => $this->upsertAll('ynh_nginx_logs', $items));

                Log::info('ynh_osquery...');

                \DB::connection('mysql_legacy')
                    ->table('ynh_osquery')
                    ->whereIn('id', fn($query) => $query->select('ynh_osquery_id')->from('ynh_osquery_latest_events'))
                    ->chunkById(100, fn(Collection $items) => $this->upsertAll('ynh_osquery', $items));

                Log::info('ynh_osquery_latest_events...');
                
                \DB::connection('mysql_legacy')
                    ->table('ynh_osquery_latest_events')
                    ->chunkById(100, fn(Collection $items) => $this->upsertAll('ynh_osquery_latest_events', $items));

                Log::info('ynh_osquery_packages...');

                \DB::connection('mysql_legacy')
                    ->table('ynh_osquery_packages')
                    ->chunkById(100, fn(Collection $items) => $this->upsertAll('ynh_osquery_packages', $items));

                Log::info('ynh_osquery_rules...');

                \DB::connection('mysql_legacy')
                    ->table('ynh_osquery_rules')
                    ->chunkById(100, fn(Collection $items) => $this->upsertAll('ynh_osquery_rules', $items));

                Log::info('ynh_ossec_checks...');

                \DB::connection('mysql_legacy')
                    ->table('ynh_ossec_checks')
                    ->chunkById(100, fn(Collection $items) => $this->upsertAll('ynh_ossec_checks', $items));

                Log::info('ynh_ossec_policies...');

                \DB::connection('mysql_legacy')
                    ->table('ynh_ossec_policies')
                    ->chunkById(100, fn(Collection $items) => $this->upsertAll('ynh_ossec_policies', $items));

                Log::info('ynh_overview...');

                \DB::connection('mysql_legacy')
                    ->table('ynh_overview')
                    ->chunkById(100, fn(Collection $items) => $this->upsertAll('ynh_overview', $items));

                Log::info('ynh_permissions...');

                \DB::connection('mysql_legacy')
                    ->table('ynh_permissions')
                    ->chunkById(100, fn(Collection $items) => $this->upsertAll('ynh_permissions', $items));

                Log::info('ynh_servers...');

                \DB::connection('mysql_legacy')
                    ->table('ynh_servers')
                    ->chunkById(100, fn(Collection $items) => $this->upsertAll('ynh_servers', $items));

                Log::info('ynh_ssh_traces...');

                \DB::connection('mysql_legacy')
                    ->table('ynh_ssh_traces')
                    ->chunkById(100, fn(Collection $items) => $this->upsertAll('ynh_ssh_traces', $items));

                Log::info('ynh_trials...');

                \DB::connection('mysql_legacy')
                    ->table('ynh_trials')
                    ->chunkById(100, fn(Collection $items) => $this->upsertAll('ynh_trials', $items));

                Log::info('ynh_users...');

                \DB::connection('mysql_legacy')
                    ->table('ynh_users')
                    ->chunkById(100, fn(Collection $items) => $this->upsertAll('ynh_users', $items));

            } catch (\Exception $e) {
                Log::error($e->getMessage());
            } finally {
                Schema::enableForeignKeyConstraints();
            }
        });
        return 0;
    }

    private function upsertAll(string $table, Collection $items)
    {
        /** @var object $item */
        foreach ($items as $item) {
            $this->upsert($table, $item);
        }
    }

    private function upsert(string $table, object $item)
    {
        $objKeys = array_keys((array)$item);
        $tblKeys = Schema::getColumnListing($table);
        $keys = array_intersect($objKeys, $tblKeys);
        $newItem = array_intersect_key((array)$item, array_flip($keys));
        \DB::connection('mysql')->table($table)->upsert($newItem, ['id'], $keys);
    }
}