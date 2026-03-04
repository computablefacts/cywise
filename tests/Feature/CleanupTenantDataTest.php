<?php

namespace Tests\Feature;

use App\Jobs\Cleanup;
use App\Models\Asset;
use App\Models\Collection;
use App\Models\Conversation;
use App\Models\File;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\TimelineFact;
use App\Models\TimelineItem;
use App\Models\Trial;
use App\Models\User;
use App\Models\Vector;
use App\Models\YnhServer;
use App\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Support\Str;
use Tests\TestCaseWithDb;
use Wave\Plan;
use Wave\Subscription;

class CleanupTenantDataTest extends TestCaseWithDb
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

    public function test_tenant_with_paying_user_is_not_cleaned_up()
    {
        NotificationFacade::fake();

        $tenant = Tenant::create(['name' => 'Paying Tenant']);
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
        ]);
        $this->makeUserPaying($user);
        Asset::create([
            'asset' => 'example.com',
            'type' => \App\Enums\AssetTypesEnum::DNS,
            'created_by' => $user->id,
        ]);

        (new Cleanup())->handle();

        $this->assertNull($tenant->fresh()->deletion_scheduled_at);
        $this->assertDatabaseHas('am_assets', ['asset' => 'example.com']);

        NotificationFacade::assertNothingSent();
    }

    public function test_tenant_without_paying_user_schedules_deletion()
    {
        NotificationFacade::fake();

        $tenant = Tenant::create(['name' => 'Non-Paying Tenant']);
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
        ]);
        Asset::create([
            'asset' => 'tobedeleted.com',
            'type' => \App\Enums\AssetTypesEnum::DNS,
            'created_by' => $user->id,
        ]);

        (new Cleanup())->handle();

        $this->assertNotNull($tenant->fresh()->deletion_scheduled_at);
        $this->assertTrue($tenant->fresh()->deletion_scheduled_at->isFuture());

        NotificationFacade::assertSentTo(
            $user,
            Notification::class,
            function ($notification, $channels) {
                return Str::contains($notification->toMailCoach(new \stdClass())['content'], 'Votre période d\'essai sur Cywise arrive à son terme.');
            }
        );
    }

    public function test_tenant_without_paying_user_and_no_data_does_not_schedule_deletion()
    {
        NotificationFacade::fake();

        $tenant = Tenant::create(['name' => 'Empty Tenant']);
        User::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        (new Cleanup())->handle();

        $this->assertNull($tenant->fresh()->deletion_scheduled_at);

        NotificationFacade::assertNothingSent();
    }

    public function test_tenant_cancels_deletion_if_user_becomes_paying()
    {
        $tenant = Tenant::create([
            'name' => 'Returning Tenant',
            'deletion_scheduled_at' => now()->addDays(2)
        ]);
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
        ]);
        $this->makeUserPaying($user);

        (new Cleanup())->handle();

        $this->assertNull($tenant->fresh()->deletion_scheduled_at);
    }

    public function test_tenant_data_is_deleted_after_delay()
    {
        NotificationFacade::fake();

        $tenant = Tenant::create([
            'name' => 'Expired Tenant',
            'deletion_scheduled_at' => now()->subMinutes(1)
        ]);
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
        ]);
        $asset = Asset::create([
            'asset' => 'deleted.com',
            'type' => \App\Enums\AssetTypesEnum::DNS,
            'created_by' => $user->id,
        ]);
        $server = YnhServer::create([
            'name' => 'Expired Server',
            'ip' => '1.2.3.4',
            'created_by' => $user->id,
        ]);
        $conversation = Conversation::create([
            'thread_id' => 'test-thread',
            'dom' => json_encode([]),
            'created_by' => $user->id,
            'format' => Conversation::FORMAT_V1,
        ]);
        $collection = Collection::create([
            'name' => 'Test Collection',
            'created_by' => $user->id,
        ]);
        $file = File::create([
            'collection_id' => $collection->id,
            'name' => 'test.txt',
            'name_normalized' => 'test.txt',
            'extension' => 'txt',
            'mime_type' => 'text/plain',
            'size' => 123,
            'md5' => 'md5',
            'sha1' => 'sha1',
            'path' => '/tmp/test.txt',
            'created_by' => $user->id,
            'secret' => 'secret',
        ]);
        $trial = Trial::create([
            'hash' => 'hash',
            'created_by' => $user->id,
        ]);
        $vector = Vector::create([
            'collection_id' => $collection->id,
            'file_id' => $file->id,
            'chunk_id' => 1,
            'locale' => 'fr',
            'hypothetical_question' => 'test?',
            'embedding' => [0.1, 0.2],
            'created_by' => $user->id,
        ]);
        $leak = TimelineItem::createItem($user->id, 'leak', now(), 0, [
            'credentials' => json_encode([['email' => 'leak@example.com', 'password' => '****', 'leak_date' => '2021-01-01']]),
        ]);
        $fact = TimelineFact::create([
            'owned_by' => $user->id,
            'attribute' => 'test',
            'type' => TimelineFact::TYPE_STRING,
            'value' => 'test',
        ]);

        (new Cleanup())->handle();

        $this->assertNull($tenant->fresh()->deletion_scheduled_at);

        $this->assertDatabaseMissing('am_assets', ['id' => $asset->id]);
        $this->assertDatabaseMissing('ynh_servers', ['id' => $server->id]);
        $this->assertDatabaseMissing('cb_conversations', ['id' => $conversation->id]);
        $this->assertDatabaseMissing('cb_files', ['id' => $file->id]);
        $this->assertDatabaseMissing('cb_collections', ['id' => $collection->id]);
        $this->assertDatabaseMissing('cb_vectors', ['id' => $vector->id]);
        $this->assertDatabaseMissing('ynh_trials', ['id' => $trial->id]);
        $this->assertDatabaseMissing('t_items', ['id' => $leak->id]);
        $this->assertDatabaseMissing('t_facts', ['id' => $fact->id]);
        $this->assertDatabaseHas('users', ['id' => $user->id]);

        NotificationFacade::assertSentTo(
            $user,
            Notification::class,
            function ($notification, $channels) {
                return Str::contains($notification->toMailCoach(new \stdClass())['content'], 'Conformément à ce qui vous a été annoncé, vos données ont maintenant été supprimées.');
            }
        );
    }

    public function test_excluded_tenant_is_not_cleaned_up()
    {
        NotificationFacade::fake();

        $tenant = Tenant::create([
            'name' => 'Excluded Tenant',
            'cleanup' => false,
            'deletion_scheduled_at' => now()->subMinutes(1)
        ]);
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
        ]);
        $asset = Asset::create([
            'asset' => 'notdeleted.com',
            'type' => \App\Enums\AssetTypesEnum::DNS,
            'created_by' => $user->id,
        ]);

        (new Cleanup())->handle();

        $this->assertNotNull($tenant->fresh()->deletion_scheduled_at);
        $this->assertDatabaseHas('am_assets', ['id' => $asset->id]);

        NotificationFacade::assertNothingSent();
    }
}
