<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/** @deprecated */
class Invitation extends Mailable
{
    use Queueable, SerializesModels;

    private \App\Models\Invitation $invitation;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(\App\Models\Invitation $invitation)
    {
        $this->invitation = $invitation;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this
            ->from(config('towerify.freshdesk.from_email'), 'Support')
            ->subject("Invitation à rejoindre Cywise")
            ->markdown('auth.invitations.email', [
                "invitation" => $this->invitation,
            ]);
    }
}
