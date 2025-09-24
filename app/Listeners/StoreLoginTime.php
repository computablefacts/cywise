<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;

class StoreLoginTime
{
    public function handle(Login $event)
    {
        session(['login_time' => now()]);
    }
}
