<?php

namespace App\Listeners;

use App\Models\AppTrace;
use App\Models\User;
use Illuminate\Auth\Events\Failed;

class LoginFailedListener extends AbstractListener
{
    protected function handle2($event)
    {
        if (!($event instanceof Failed)) {
            throw new \Exception('Invalid event type!');
        }

        $email = $event->credentials['email'];
        $password = $event->credentials['password'];
        $user = User::where('email', $email)->first();

        /** @var AppTrace $trace */
        $trace = AppTrace::create([
            'user_id' => $user?->id,
            'verb' => 'GET',
            'endpoint' => "/auth/login?email={$email}",
            'duration_in_ms' => 10,
            'failed' => true,
        ]);
    }
}
