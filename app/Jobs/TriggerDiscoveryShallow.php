<?php

namespace App\Jobs;

use App\Enums\AssetTypesEnum;
use App\Events\CreateAsset;
use App\Helpers\VulnerabilityScannerApiUtilsFacade as ApiUtils;
use App\Models\Asset;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class TriggerDiscoveryShallow implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    public $maxExceptions = 1;
    public $timeout = 15 * 60; // 15mn

    public function __construct()
    {
        //
    }

    public function handle()
    {
        Tenant::all()
            ->map(fn(Tenant $tenant) => User::where('tenant_id', $tenant->id)->orderBy('created_at')->first())
            ->filter(fn(?User $user) => isset($user))
            ->each(function (User $user) {

                $user->actAs(); // otherwise the tenant will not be properly set

                Asset::whereNull('discovery_id')
                    ->where('type', AssetTypesEnum::DNS)
                    ->get()
                    ->map(fn(Asset $asset) => $asset->tld())
                    ->filter(fn(?string $tld) => !empty($tld))
                    ->unique()
                    ->each(function (string $tld) use ($user) {

                        $discovered = $this->discover($tld);

                        if (isset($discovered['subdomains']) && count($discovered['subdomains'])) {
                            collect($discovered['subdomains'])
                                ->filter(fn(?string $domain) => !empty($domain))
                                ->filter(fn(string $domain) => $user->email === config('towerify.admin.email') || !Str::endsWith($domain, ['computablefacts.com', 'computablefacts.io', 'towerify.io', 'cywise.io']))
                                ->each(fn(string $domain) => CreateAsset::dispatch($user, $domain, true));
                        }
                    });
            });
    }

    private function discover(string $tld): array
    {
        return ApiUtils::discover_public($tld);
    }
}
