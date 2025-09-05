<?php

namespace App\Jobs;

use App\Events\SendAuditReport;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class TriggerSendAuditReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    public $maxExceptions = 1;
    public $timeout = 3 * 180; // 9mn

    public function __construct()
    {
        //
    }

    public function handle()
    {
        $domains = collect(config('towerify.telescope.whitelist.domains'))->map(fn(string $domain) => '@' . $domain)->toArray();
        User::query()
            ->get()
            ->filter(fn(User $user) => !$user->isAdmin()) // do not spam the admin
            ->filter(fn(User $user) => $user->gets_audit_report)
            ->filter(fn(User $user) => !$user->isBarredFromAccessingTheApp())
            ->filter(fn(User $user) => $user->canUseAdversaryMeter())
            ->filter(fn(User $user) => !Str::endsWith($user->email, $domains) || !Str::contains(Str::before($user->email, '@'), '+')) // do not send emails to debug accounts
            ->each(fn(User $user) => SendAuditReport::dispatch($user));
    }
}
