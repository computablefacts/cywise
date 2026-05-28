<?php

namespace App\Listeners;

use App\Events\AssetsDiscovery;
use App\Events\CreateAsset;
use App\Http\Procedures\AssetsProcedure;
use App\Http\Requests\JsonRpcRequest;
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
                $assets->each(fn(string $domain) => CreateAsset::dispatch($user, $domain, $user->auto_monitor_new_assets));

                Log::debug("Assets discovery ended for {$tld} ({$assets->count()})");
            } else {
                Log::debug("Assets discovery ended for {$tld} (0)");
            }
        } catch (\Exception $e) {
            Log::error($e);
        }
    }
}
