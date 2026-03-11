<?php

namespace Tests\Feature;

use App\Jobs\Cleanup;
use App\Models\Asset;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\Trial;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCaseWithDb;
use Wave\Plan;
use Wave\Subscription;

class CleanupTrialAssetsTest extends TestCaseWithDb
{
    private function makeUserPaying(User $user)
    {
        $role = Role::firstOrCreate(['name' => Role::STANDARD_PLAN, 'guard_name' => 'web']);
        $plan = Plan::create([
            'name' => 'Basic Plan',
            'description' => 'A basic plan',
            'features' => 'Feature 1, Feature 2',
            'active' => 1,
            'role_id' => $role->id,
            'monthly_price' => 9.99,
            'yearly_price' => 99.99,
            'onetime_price' => 0,
            'default' => 0,
            'currency' => 'EUR',
        ]);
        Subscription::create([
            'billable_id' => $user->id,
            'billable_type' => 'user',
            'plan_id' => $plan->id,
            'status' => 'active',
            'vendor_slug' => 'slug',
            'vendor_product_id' => 'prod',
            'vendor_transaction_id' => 'trans',
            'vendor_customer_id' => 'cust',
            'vendor_subscription_id' => 'sub',
            'cycle' => 'month',
        ]);
    }

    public function test_cleanup_deletes_old_trial_assets_without_subscription()
    {
        // Disable foreign key checks for this test or create necessary relations
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $tenant = Tenant::create(['name' => 'Test Tenant']);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $oldTrial = Trial::create([
            'hash' => 'old_trial',
            'created_by' => $user->id
        ]);
        $oldTrial->created_at = Carbon::now()->subDays(16);
        $oldTrial->save();

        $asset = Asset::create([
            'asset' => 'old-trial-asset.com',
            'type' => \App\Enums\AssetTypesEnum::DNS,
            'ynh_trial_id' => $oldTrial->id,
            'created_by' => $user->id
        ]);

        // No subscription for this user/tenant
        (new Cleanup())->handle();

        $this->assertDatabaseMissing('am_assets', ['id' => $asset->id]);
    }

    public function test_cleanup_sets_trial_id_to_null_if_subscription_exists()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $tenant = Tenant::create(['name' => 'Subscribed Tenant']);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->makeUserPaying($user);

        $oldTrial = Trial::create([
            'hash' => 'subscribed_trial',
            'created_by' => $user->id
        ]);
        $oldTrial->created_at = Carbon::now()->subDays(16);
        $oldTrial->save();

        $asset = Asset::create([
            'asset' => 'subscribed-asset.com',
            'type' => \App\Enums\AssetTypesEnum::DNS,
            'ynh_trial_id' => $oldTrial->id,
            'created_by' => $user->id
        ]);

        (new Cleanup())->handle();

        $this->assertDatabaseHas('am_assets', [
            'id' => $asset->id,
            'ynh_trial_id' => null
        ]);
    }

    public function test_cleanup_does_not_touch_recent_trial_assets()
    {
        $tenant = Tenant::create(['name' => 'Recent Trial Tenant']);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $recentTrial = Trial::create([
            'hash' => 'recent_trial',
            'created_at' => Carbon::now()->subDays(10),
            'created_by' => $user->id
        ]);

        $asset = Asset::create([
            'asset' => 'recent-asset.com',
            'type' => \App\Enums\AssetTypesEnum::DNS,
            'ynh_trial_id' => $recentTrial->id,
            'created_by' => $user->id
        ]);

        (new Cleanup())->handle();

        $this->assertDatabaseHas('am_assets', [
            'id' => $asset->id,
            'ynh_trial_id' => $recentTrial->id
        ]);
    }
}
