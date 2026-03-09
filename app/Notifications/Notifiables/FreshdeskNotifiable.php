<?php

namespace App\Notifications\Notifiables;

use Illuminate\Notifications\Notifiable;

class FreshdeskNotifiable
{
    use Notifiable;

    /**
     * Route notifications for the mail channel.
     *
     * @return string
     */
    public function routeNotificationForMail(): string
    {
        return config('towerify.freshdesk.to_email');
    }

    /**
     * Get the key name for the notifiable.
     *
     * @return string
     */
    public function getKey(): string
    {
        return 'freshdesk';
    }
}
