<?php

namespace Tests\Feature;

use App\Enums\AssetTypesEnum;
use App\Events\AssetsDiscovery;
use App\Helpers\VulnerabilityScannerApiUtilsFacade as ApiUtils;
use App\Listeners\AssetsDiscoveryListener;
use App\Models\Asset;
use App\Models\User;

class AssetsDiscoveryListenerTest extends \Tests\TestCaseWithDb
{
    public function test_it_inherits_auto_monitor_subdomains_from_parent_when_false()
    {
        // 1. Préparation des données
        /** @var User $user */
        $user = User::factory()->create([
            'email' => 'test@example.com'
        ]);
        $user->actAs();

        // Créer l'actif parent avec auto_monitor_new_subdomains = false
        $parentAsset = Asset::create([
            'asset' => 'example.com',
            'type' => AssetTypesEnum::DNS,
            'is_monitored' => true,
            'auto_monitor_new_subdomains' => false,
            'created_by' => $user->id,
            'tld' => 'example.com',
        ]);

        // 2. Mock de l'API de découverte
        ApiUtils::shouldReceive('discover_public')
            ->once()
            ->with('example.com')
            ->andReturn([
                'subdomains' => ['sub.example.com'],
            ]);

        // 3. Exécution du listener
        $event = new AssetsDiscovery($user, 'example.com');
        (new AssetsDiscoveryListener())->handle($event);

        // 4. Vérifications
        $subdomain = Asset::where('asset', 'sub.example.com')
            ->where('created_by', $user->id)
            ->first();

        $this->assertNotNull($subdomain, 'Le sous-domaine devrait avoir été créé.');
        $this->assertFalse($subdomain->is_monitored, 'Le sous-domaine ne devrait pas être surveillé car le parent a auto_monitor_new_subdomains à false.');
        $this->assertFalse($subdomain->auto_monitor_new_subdomains, 'La case auto_monitor_new_subdomains devrait être à faux par défaut pour le sous-domaine.');
    }

    public function test_it_inherits_auto_monitor_subdomains_from_parent_when_true()
    {
        // 1. Préparation des données
        /** @var User $user */
        $user = User::factory()->create([
            'email' => 'test-true@example.com'
        ]);
        $user->actAs();

        // Créer l'actif parent avec auto_monitor_new_subdomains = true
        $parentAsset = Asset::create([
            'asset' => 'example.org',
            'type' => AssetTypesEnum::DNS,
            'is_monitored' => true,
            'auto_monitor_new_subdomains' => true,
            'created_by' => $user->id,
            'tld' => 'example.org',
        ]);

        // 2. Mock de l'API de découverte
        ApiUtils::shouldReceive('discover_public')
            ->once()
            ->with('example.org')
            ->andReturn([
                'subdomains' => ['sub.example.org'],
            ]);

        // 3. Exécution du listener
        $event = new AssetsDiscovery($user, 'example.org');
        (new AssetsDiscoveryListener())->handle($event);

        // 4. Vérifications
        $subdomain = Asset::where('asset', 'sub.example.org')
            ->where('created_by', $user->id)
            ->first();

        $this->assertNotNull($subdomain, 'Le sous-domaine devrait avoir été créé.');
        $this->assertTrue($subdomain->is_monitored, 'Le sous-domaine devrait être surveillé car le parent a auto_monitor_new_subdomains à true.');
        $this->assertFalse($subdomain->auto_monitor_new_subdomains, 'La case auto_monitor_new_subdomains devrait être à false par défaut pour le sous-domaine.');
    }

    public function test_it_does_not_inherit_from_sibling_subdomain()
    {
        /** @var User $user */
        $user = User::factory()->create([
            'email' => 'test-sibling@example.com'
        ]);
        $user->actAs();

        // Créer l'actif parent (example.com) avec auto_monitor_new_subdomains = true
        Asset::create([
            'asset' => 'example.com',
            'type' => AssetTypesEnum::DNS,
            'is_monitored' => true,
            'auto_monitor_new_subdomains' => true,
            'created_by' => $user->id,
            'tld' => 'example.com',
        ]);

        // Créer un frère (www.example.com) avec auto_monitor_new_subdomains = false
        Asset::create([
            'asset' => 'www.example.com',
            'type' => AssetTypesEnum::DNS,
            'is_monitored' => false,
            'auto_monitor_new_subdomains' => false,
            'created_by' => $user->id,
            'tld' => 'example.com',
        ]);

        // Découverte de www2.example.com
        // Il devrait hériter de example.com (true) et PAS de www.example.com (false)
        ApiUtils::shouldReceive('discover_public')
            ->once()
            ->with('example.com')
            ->andReturn(['subdomains' => ['www2.example.com']]);

        $event = new AssetsDiscovery($user, 'example.com');
        (new AssetsDiscoveryListener())->handle($event);

        $subdomain = Asset::where('asset', 'www2.example.com')
            ->where('created_by', $user->id)
            ->first();

        $this->assertNotNull($subdomain, 'Le sous-domaine www2.example.com devrait avoir été créé.');
        $this->assertTrue($subdomain->is_monitored, 'www2.example.com devrait hériter de example.com (true) et non de www.example.com.');
        $this->assertFalse($subdomain->auto_monitor_new_subdomains, 'La case auto_monitor_new_subdomains devrait être à false par défaut pour le sous-domaine.');
    }
}
