<?php

namespace App\Notifications\Channels;

use App\Models\User;
use App\Services\MessagingService;
use Illuminate\Notifications\Notification;

class TelegramChannel
{
    public function __construct(protected MessagingService $messagingService)
    {
    }

    public function send(User $notifiable, Notification $notification): void
    {
        if (method_exists($notification, 'toTelegram')) {
            $message = $notification->toTelegram($notifiable);
            if (!empty($message)) {
                $this->messagingService->sendTelegram($notifiable, $message);
            }
        }
    }
}
