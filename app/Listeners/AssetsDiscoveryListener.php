<?php

namespace App\Listeners;

use App\Events\AssetsDiscovery;
use App\Events\CreateAsset;
use App\Http\Procedures\AssetsProcedure;
use App\Http\Requests\JsonRpcRequest;
use App\Models\Asset;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class AssetsDiscoveryListener extends AbstractListener
{
    public function viaQueue(): string
    {
        return self::LOW;
    }

    protected function handle2($event)
    {
        if (!($event instanceof AssetsDiscovery)) {
            throw new \Exception('Invalid event type!');
        }

        /** @var User $user */
        $user = $event->user;
        $user->actAs();
        $tld = $event->tld;

        try {
            Log::debug("Starting assets discovery for {$tld}...");

            $request = new JsonRpcRequest(['domain' => $tld]);
            $request->setUserResolver(fn() => $user);
            $response = (new AssetsProcedure())->discover($request);

            if (!empty($response['subdomains'])) {

                $assets = collect($response['subdomains'])->filter(fn(?string $domain) => !empty($domain));
                $assets->each(function (string $domain) use ($user, $tld) {

                    // On cherche le parent le plus proche (le plus long) qui correspond au domaine ou un de ses parents.
                    // Exemple pour www2.example.com : parents possibles ["www2.example.com", "example.com"]
                    $parts = explode('.', $domain);
                    $candidates = [];

                    for ($i = 0; $i < count($parts); $i++) {
                        $candidates[] = implode('.', array_slice($parts, $i));
                    }

                    /** @var Asset $parent */
                    $parent = Asset::query()
                        ->whereIn('asset', $candidates)
                        ->where('tld', $tld)
                        ->orderByRaw('LENGTH(asset) DESC')
                        ->first();

                    CreateAsset::dispatch($user, $domain, $parent->auto_monitor_new_subdomains ?? false);
                });

                Log::debug("Assets discovery ended for {$tld} ({$assets->count()})");
            } else {
                Log::debug("Assets discovery ended for {$tld} (0)");
            }
        } catch (\Exception $e) {
            Log::error($e);
        }
    }
}
