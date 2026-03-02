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

    public static function viaEmail(string $content, string $subject = 'Cywise', ?string $from = null): Notification
    {
        return new Notification($content, $subject, $from, [MailCoachChannel::class]);
    }

    /**
     * Create a new notification instance.
     *
     * @param string $content
     * @param string $subject
     * @param string|null $from
     * @param array|null $channels
     */
    public function __construct(protected string $content, protected string $subject = 'Cywise', protected ?string $from = null, protected ?array $channels = null)
    {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return $this->channels ?? [WhatsAppChannel::class, TelegramChannel::class];
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
