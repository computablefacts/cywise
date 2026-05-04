<?php

namespace App\Notifications\Notifiables;

class FreshdeskNotifiable extends AbstractNotifiable
{
    public function routeNotificationForMail(): string
    {
        return config('towerify.freshdesk.to_email');
    }

    public function getKey(): string
    {
        return 'freshdesk';
    }
}
