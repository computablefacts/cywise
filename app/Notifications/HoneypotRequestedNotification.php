<?php

namespace App\Notifications;

use App\Enums\HoneypotCloudProvidersEnum;
use App\Enums\HoneypotCloudSensorsEnum;
use App\Notifications\Channels\MailCoachChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification as BaseNotification;

class HoneypotRequestedNotification extends BaseNotification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public                            $honeypotId,
        public HoneypotCloudSensorsEnum   $sensor,
        public HoneypotCloudProvidersEnum $provider,
        public string                     $dns,
        public string                     $user
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
            'subject' => "Cywise : Honeypot requested by {$this->user}",
            'title' => "Honeypot requested by {$this->user}",
            'id' => $this->honeypotId,
            'cloud_provider' => $this->provider->value,
            'cloud_sensor' => $this->sensor->value,
            'dns' => $this->dns,
            'mailcoach_template' => 'honeypot-requested',
        ];
    }
}
