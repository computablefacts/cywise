<?php

namespace Tests\Unit;

use App\Notifications\Notifiables\FreshdeskNotifiable;
use App\Notifications\Notification;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Tests\TestCaseNoDb;

class FreshdeskNotifiableTest extends TestCaseNoDb
{
    public function test_freshdesk_notifiable_routes_to_config_email(): void
    {
        Config::set('towerify.freshdesk.to_email', 'support@test.com');
        
        $notifiable = new FreshdeskNotifiable();
        
        $this->assertEquals('support@test.com', $notifiable->routeNotificationForMail());
    }

    public function test_freshdesk_notifiable_can_receive_notification(): void
    {
        NotificationFacade::fake();
        
        $notifiable = new FreshdeskNotifiable();
        $notification = new Notification("Test content", "Test subject");
        
        $notifiable->notify($notification);
        
        NotificationFacade::assertSentTo(
            $notifiable,
            Notification::class,
            function ($sentNotification) {
                return $sentNotification->toMailCoach(new FreshdeskNotifiable())['subject'] === 'Test subject';
            }
        );
    }
}
