<?php

namespace App\Notifications\Channels;

use App\Models\User;
use App\Services\MessagingService;
use Illuminate\Notifications\Notification;

class MailCoachChannel
{
    public function __construct(protected MessagingService $messagingService)
    {
    }

    public function send(User $notifiable, Notification $notification): void
    {
        if (method_exists($notification, 'toMailCoach')) {
            $data = $notification->toMailCoach($notifiable);
            if (!empty($data)) {
                $this->messagingService->sendMailCoach(
                    $notifiable,
                    $data['subject'] ?? '',
                    $data['title'] ?? '',
                    $data['content'] ?? '',
                    $data['mailcoach_template'] ?? null,
                    $data,
                    $data['from'] ?? null
                );
            }
        }
    }
}
