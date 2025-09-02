<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/** @deprecated */
class HoneypotRequested extends Mailable
{
    use Queueable, SerializesModels;

    private string $email;
    private string $name;
    private string $emailSubject;
    private array $emailBody;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(string $email, string $name, string $subject, array $body)
    {
        $this->email = $email;
        $this->name = $name;
        $this->emailSubject = $subject;
        $this->emailBody = $body;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this
            ->from($this->email, $this->name)
            ->subject("Cywise : {$this->emailSubject}")
            ->markdown('modules.adversary-meter.email.honeypot-requested', [
                'params' => $this->emailBody
            ]);
    }
}
