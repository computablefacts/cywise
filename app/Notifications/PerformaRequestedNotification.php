<?php

namespace App\Notifications;

use App\Notifications\Channels\MailCoachChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification as BaseNotification;

class PerformaRequestedNotification extends BaseNotification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public        $userId,
        public string $userEmail,
        public string $dns,
        public string $secret
    )
    {
    }

    public function via(object $notifiable): array
    {
        return [MailCoachChannel::class];
    }

    public function toMailCoach(object $notifiable): array
    {
        return [
            'subject' => "Cywise : Performa requested by {$this->userEmail}",
            'title' => "Performa requested by {$this->userEmail}",
            'dns' => "{$this->dns}.cywise.io",
            'secret' => $this->secret,
            'id' => $this->userId,
            'mailcoach_template' => 'performa-requested',
        ];
    }
}
