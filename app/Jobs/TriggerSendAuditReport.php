<?php

namespace App\Jobs;

use App\Events\SendAuditReport;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

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
        User::query()
            ->where('gets_audit_report', true)
            ->get()
            ->filter(fn(User $user) => !$user->isCywiseAdmin()) // do not spam the admin
            ->each(fn(User $user) => SendAuditReport::dispatch($user));
    }
}
