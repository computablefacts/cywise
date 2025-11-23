<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SendAuditReport
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public User $user;
    public bool $isOnboarding;

    public function __construct(User $user, bool $isOnboarding = false)
    {
        $this->user = $user;
        $this->isOnboarding = $isOnboarding;
    }
}
