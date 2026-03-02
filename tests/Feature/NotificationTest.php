<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\Notification;
use App\Services\MessagingService;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Mockery;
use Tests\TestCaseWithDb;

class NotificationTest extends TestCaseWithDb
{
    public function test_notification_sends_to_telegram_and_whatsapp(): void
    {
        NotificationFacade::fake();

        $user = User::factory()->create([
            'telegram_bot_token' => 'bot123',
            'telegram_chat_id' => 'chat123',
            'whatsapp_access_token' => 'wa123',
            'whatsapp_phone_number_id' => 'pid123',
            'whatsapp_phone_number' => '123456789',
        ]);

        $message = "Hello <b>World</b>";
        $user->notify(new Notification($message));

        NotificationFacade::assertSentTo(
            $user,
            Notification::class,
            function ($notification, $channels) {
                return in_array(\App\Notifications\Channels\TelegramChannel::class, $channels) &&
                       in_array(\App\Notifications\Channels\WhatsAppChannel::class, $channels);
            }
        );
    }

    public function test_messaging_service_formatting(): void
    {
        $service = new MessagingService();
        $html = "<p>Hello</p><br><b>World</b>";

        $telegram = $service->formatForTelegram($html);
        $this->assertEquals("Hello\n\n<b>World</b>", $telegram);

        $whatsapp = $service->formatForWhatsApp($html);
        $this->assertEquals("Hello\n\n*World*", $whatsapp);
    }

    public function test_notification_content_is_formatted(): void
    {
        $user = User::factory()->make();
        $notification = new Notification("<p>Hello</p><b>World</b>");

        $this->assertEquals("Hello\n\n<b>World</b>", $notification->toTelegram($user));
        $this->assertEquals("Hello\n\n*World*", $notification->toWhatsApp($user));
    }
}
