<?php

namespace App\Notifications\Channels;

use App\Models\User;
use App\Services\MessagingService;
use Illuminate\Notifications\Notification;

class WhatsAppChannel
{
    public function __construct(protected MessagingService $messagingService)
    {
    }

    public function send(User $notifiable, Notification $notification): void
    {
        if (method_exists($notification, 'toWhatsApp')) {
            $message = $notification->toWhatsApp($notifiable);
            if (!empty($message)) {
                $this->messagingService->sendWhatsApp($notifiable, $message);
            }
        }
    }
}
