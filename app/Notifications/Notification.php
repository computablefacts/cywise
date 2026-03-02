<?php

namespace App\Notifications;

use App\Notifications\Channels\MailCoachChannel;
use App\Notifications\Channels\TelegramChannel;
use App\Notifications\Channels\WhatsAppChannel;
use App\Services\MessagingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification as BaseNotification;

class Notification extends BaseNotification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(protected string $content, protected string $subject = 'Cywise', protected ?string $from = null)
    {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return [TelegramChannel::class, WhatsAppChannel::class, MailCoachChannel::class];
    }

    public function toMailCoach(object $notifiable): array
    {
        return [
            'subject' => $this->subject,
            'title' => $this->subject,
            'content' => $this->content,
            'from' => $this->from,
        ];
    }

    public function toTelegram(object $notifiable): string
    {
        return app(MessagingService::class)->formatForTelegram($this->content);
    }

    public function toWhatsApp(object $notifiable): string
    {
        return app(MessagingService::class)->formatForWhatsApp($this->content);
    }
}
