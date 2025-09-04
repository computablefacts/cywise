<?php

namespace App\Listeners;

use App\Models\AppTrace;
use App\Models\User;
use Illuminate\Auth\Events\Login;

class LoginSucceededListener extends AbstractListener
{
    protected function handle2($event)
    {
        if (!($event instanceof Login)) {
            throw new \Exception('Invalid event type!');
        }
        if ($event->guard === 'web') {
            
            /** @var User $user */
            $user = $event->user;

            /** @var AppTrace $trace */
            $trace = AppTrace::create([
                'user_id' => $user?->id,
                'verb' => 'GET',
                'endpoint' => "/auth/login?email={$user->email}",
                'duration_in_ms' => 10,
                'failed' => false,
            ]);
        }
    }
}
