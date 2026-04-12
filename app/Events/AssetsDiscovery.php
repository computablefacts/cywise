<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AssetsDiscovery
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public User $user;
    public string $tld;

    public function __construct(User $user, string $tld)
    {
        $this->user = $user;
        $this->tld = $tld;
    }
}
