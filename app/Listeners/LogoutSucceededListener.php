<?php

namespace App\Listeners;

use App\Models\AppTrace;
use App\Models\User;
use Illuminate\Auth\Events\Logout;

class LogoutSucceededListener extends AbstractListener
{
    protected function handle2($event)
    {
        if (!($event instanceof Logout)) {
            throw new \Exception('Invalid event type!');
        }

        /** @var User $user */
        $user = $event->user;

        /** @var AppTrace $trace */
        $trace = AppTrace::create([
            'user_id' => $user?->id,
            'verb' => 'GET',
            'endpoint' => "/auth/logout?email={$user->email}",
            'duration_in_ms' => 10,
            'failed' => false,
        ]);
    }
}
