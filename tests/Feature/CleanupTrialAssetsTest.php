<?php

namespace Tests\Feature;

use App\Jobs\Cleanup;
use App\Models\Asset;
use App\Models\Tenant;
use App\Models\Trial;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCaseWithDb;
use Wave\Subscription;

class CleanupTrialAssetsTest extends TestCaseWithDb
{
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

        // Create a subscription for the user
        Subscription::create([
            'billable_type' => get_class($user),
            'billable_id' => $user->id,
            'plan_id' => 1, // Assume plan 1 exists or use a real ID
            'status' => 'active',
            'vendor_slug' => 'paddle',
            'vendor_product_id' => 'prod_123',
            'vendor_transaction_id' => 'trans_123',
            'vendor_customer_id' => 'cust_123',
            'vendor_subscription_id' => 'sub_123',
            'cycle' => 'month',
        ]);

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
