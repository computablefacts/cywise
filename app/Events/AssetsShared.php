<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AssetsShared
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public User $owner;

    public User $recipient;

    public array $tags;

    public bool $newRecipient;

    public function __construct(User $owner, User $recipient, array $tags, bool $newRecipient = false)
    {
        $this->owner = $owner;
        $this->recipient = $recipient;
        $this->tags = $tags;
        $this->newRecipient = $newRecipient;
    }
}
