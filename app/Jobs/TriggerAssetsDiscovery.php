<?php

namespace App\Jobs;

use App\Enums\AssetTypesEnum;
use App\Events\AssetsDiscovery;
use App\Models\Asset;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class TriggerAssetsDiscovery implements ShouldQueue
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
                    ->each(fn(string $tld) => AssetsDiscovery::dispatch($user, $tld));
            });
    }
}
