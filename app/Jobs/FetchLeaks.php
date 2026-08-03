<?php

namespace App\Jobs;

use App\Http\Procedures\LeaksProcedure;
use App\Http\Requests\JsonRpcRequest;
use App\Models\Asset;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FetchLeaks implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    public $maxExceptions = 1;
    public $timeout = 30 * 60; // 30mn

    public function __construct()
    {
        //
    }

    public function handle()
    {
        Log::debug("Starting global leaks fetch...");

        Tenant::all()
            ->map(fn(Tenant $tenant) => User::where('tenant_id', $tenant->id)->orderBy('created_at')->first())
            ->filter(fn(?User $user) => isset($user))
            ->each(function (User $user) {

                $user->actAs();

                $tlds = Asset::where('is_monitored', true)
                    ->get()
                    ->map(fn(Asset $asset) => $asset->tld())
                    ->filter(fn(?string $tld) => !empty($tld))
                    ->unique();

                if ($tlds->isEmpty()) {
                    return;
                }

                Log::debug("Fetching leaks for tenant {$user->tenant_id} ({$tlds->count()} TLDs)...");

                $request = new JsonRpcRequest();
                $request->setUserResolver(fn() => $user);

                (new LeaksProcedure())->list($request);
            });

        Log::debug("Global leaks fetch ended.");
    }
}
