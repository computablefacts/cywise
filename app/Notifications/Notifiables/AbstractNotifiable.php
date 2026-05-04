<?php

namespace App\Notifications\Notifiables;

use Illuminate\Notifications\Notifiable;

abstract class AbstractNotifiable
{
    use Notifiable;

    /**
     * Route notifications for the mail channel.
     *
     * @return string
     */
    public abstract function routeNotificationForMail(): string;

    /**
     * Get the key name for the notifiable.
     *
     * @return string
     */
    public abstract function getKey(): string;
}
