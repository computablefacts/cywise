<?php

namespace Tests\Feature;

use App\Http\Procedures\CyberBuddyProcedure;
use App\Http\Requests\JsonRpcRequest;
use App\Models\Asset;
use App\Models\Conversation;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\CywiseSeeder;
use Illuminate\Support\Str;
use Tests\TestCaseWithDb;

class CyberBuddyProcedureTest extends TestCaseWithDb
{
    protected User $user;
    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        // Configurer les plans Stripe pour CywiseSeeder
        config()->set([

            'towerify.stripe.plans.essential.name' => 'Essentiel',
            'towerify.stripe.plans.essential.description' => 'Description Essentiel',
            'towerify.stripe.plans.essential.features' => 'Features Essentiel',
            'towerify.stripe.plans.essential.monthly_price' => '150',
            'towerify.stripe.plans.essential.monthly_price_id' => 'price_essential_monthly',
            'towerify.stripe.plans.essential.yearly_price' => '1500',
            'towerify.stripe.plans.essential.yearly_price_id' => 'price_essential_yearly',
            'towerify.stripe.plans.essential.onetime_price' => null,
            'towerify.stripe.plans.essential.onetime_price_id' => null,

            'towerify.stripe.plans.standard.name' => 'Standard',
            'towerify.stripe.plans.standard.description' => 'Description Standard',
            'towerify.stripe.plans.standard.features' => 'Features Standard',
            'towerify.stripe.plans.standard.monthly_price' => '400',
            'towerify.stripe.plans.standard.monthly_price_id' => 'price_standard_monthly',
            'towerify.stripe.plans.standard.yearly_price' => '4000',
            'towerify.stripe.plans.standard.yearly_price_id' => 'price_standard_yearly',
            'towerify.stripe.plans.standard.onetime_price' => null,
            'towerify.stripe.plans.standard.onetime_price_id' => null,

            'towerify.stripe.plans.premium.name' => 'Premium',
            'towerify.stripe.plans.premium.description' => 'Description Premium',
            'towerify.stripe.plans.premium.features' => 'Features Premium',
            'towerify.stripe.plans.premium.monthly_price' => '600',
            'towerify.stripe.plans.premium.monthly_price_id' => 'price_premium_monthly',
            'towerify.stripe.plans.premium.yearly_price' => '6000',
            'towerify.stripe.plans.premium.yearly_price_id' => 'price_premium_yearly',
            'towerify.stripe.plans.premium.onetime_price' => null,
            'towerify.stripe.plans.premium.onetime_price_id' => null,
        ]);

        // 1. Initialiser les données minimales nécessaires de CyberBuddy
        $this->seed(CywiseSeeder::class);

        $this->tenant = Tenant::create(['name' => 'Test Tenant']);
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        // 2. Initialiser les prompts pour l'utilisateur
        $this->user->init();
    }

    public function test_cyberbuddy_monitors_existing_asset()
    {
        $this->actingAs($this->user);

        /** @var Conversation $conversation */
        $conversation = Conversation::create([
            'thread_id' => Str::random(10),
            'dom' => json_encode([]),
            'format' => Conversation::FORMAT_V1,
            'created_by' => $this->user->id,
        ]);

        // 1. L'actif existe déjà et n'est pas surveillé
        Asset::create([
            'asset' => 'existing.com',
            'type' => \App\Enums\AssetTypesEnum::DNS,
            'is_monitored' => false,
            'created_by' => $this->user->id,
            'tenant_id' => $this->tenant->id,
        ]);

        // 2. Appel à CyberBuddy
        $procedure = new CyberBuddyProcedure();
        $request = new JsonRpcRequest([
            'thread_id' => $conversation->thread_id,
            'directive' => 'surveille existing.com',
        ]);
        $request->setUserResolver(fn() => $this->user);
        $procedure->ask($request);

        // 3. Vérification de l'état de la base
        $asset = Asset::where('asset', 'existing.com')->first();
        $this->assertTrue((bool)$asset->is_monitored, "L'actif existing.com devrait être monitoré");
    }

    public function test_cyberbuddy_creates_and_monitors_non_existing_asset()
    {
        $this->actingAs($this->user);

        /** @var Conversation $conversation */
        $conversation = Conversation::create([
            'thread_id' => Str::random(10),
            'dom' => json_encode([]),
            'format' => Conversation::FORMAT_V1,
            'created_by' => $this->user->id,
        ]);

        // 1. L'actif n'existe pas
        $this->assertDatabaseMissing('am_assets', ['asset' => 'new-asset.com']);

        // 2. Appel à CyberBuddy
        $procedure = new CyberBuddyProcedure();
        $request = new JsonRpcRequest([
            'thread_id' => $conversation->thread_id,
            'directive' => "surveille new-asset.com",
        ]);
        $request->setUserResolver(fn() => $this->user);
        $procedure->ask($request);

        // 3. Vérification de l'état de la base
        $asset = Asset::where('asset', 'new-asset.com')->first();
        $this->assertNotNull($asset, "L'actif new-asset.com devrait avoir été créé");
        $this->assertTrue((bool)$asset->is_monitored, "L'actif new-asset.com devrait être monitoré");
    }
}
