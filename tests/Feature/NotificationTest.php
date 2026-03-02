<?php

namespace Tests\Feature;

use App\Enums\HoneypotCloudProvidersEnum;
use App\Enums\HoneypotCloudSensorsEnum;
use App\Models\User;
use App\Notifications\HoneypotRequestedNotification;
use App\Notifications\Notification;
use App\Notifications\PerformaRequestedNotification;
use App\Services\MessagingService;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Tests\TestCaseWithDb;

class NotificationTest extends TestCaseWithDb
{
    public function test_honeypot_requested_notification_to_mailcoach_data(): void
    {
        $user = User::factory()->make();
        $notification = new HoneypotRequestedNotification(
            123,
            HoneypotCloudSensorsEnum::HTTP,
            HoneypotCloudProvidersEnum::AWS,
            'test.cywise.io',
            'user@example.com'
        );
        $data = $notification->toMailCoach($user);

        $this->assertEquals("Cywise : Honeypot requested by user@example.com", $data['subject']);
        $this->assertEquals(123, $data['id']);
        $this->assertEquals('HTTP', $data['cloud_sensor']);
        $this->assertEquals('AWS', $data['cloud_provider']);
        $this->assertEquals('test.cywise.io', $data['dns']);
        $this->assertEquals('honeypot-requested', $data['mailcoach_template']);
    }

    public function test_performa_requested_notification_to_mailcoach_data(): void
    {
        $user = User::factory()->make();
        $notification = new PerformaRequestedNotification(
            456,
            'user@example.com',
            'a-b-c',
            'secret123'
        );
        $data = $notification->toMailCoach($user);

        $this->assertEquals("Cywise : Performa requested by user@example.com", $data['subject']);
        $this->assertEquals(456, $data['id']);
        $this->assertEquals('a-b-c.cywise.io', $data['dns']);
        $this->assertEquals('secret123', $data['secret']);
        $this->assertEquals('performa-requested', $data['mailcoach_template']);
    }

    public function test_notification_with_custom_from(): void
    {
        $user = User::factory()->make();
        $notification = new Notification("content", "subject", "custom@example.com");
        $data = $notification->toMailCoach($user);

        $this->assertEquals("custom@example.com", $data['from']);
    }

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
                    in_array(\App\Notifications\Channels\WhatsAppChannel::class, $channels) &&
                    in_array(\App\Notifications\Channels\MailCoachChannel::class, $channels);
            }
        );
    }

    public function test_notification_to_mailcoach_data(): void
    {
        $user = User::factory()->make();
        $notification = new Notification("Some content", "Some subject");
        $data = $notification->toMailCoach($user);

        $this->assertEquals("Some subject", $data['subject']);
        $this->assertEquals("Some subject", $data['title']);
        $this->assertEquals("Some content", $data['content']);
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

    public function test_notification_sends_to_single_channel(): void
    {
        NotificationFacade::fake();

        $user = User::factory()->create();

        $user->notify(new Notification("Hello", "Subject", null, [\App\Notifications\Channels\TelegramChannel::class]));

        NotificationFacade::assertSentTo(
            $user,
            Notification::class,
            function ($notification, $channels) {
                return count($channels) === 1 && $channels[0] === \App\Notifications\Channels\TelegramChannel::class;
            }
        );
    }
}
