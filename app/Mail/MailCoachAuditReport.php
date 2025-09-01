<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Spatie\MailcoachMailer\Concerns\UsesMailcoachMail;

class MailCoachAuditReport extends Mailable
{
    use Queueable, SerializesModels, UsesMailcoachMail;

    private string $bodyHtml;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(string $body)
    {
        $this->bodyHtml = $body;
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
            ->mailcoachMail('audit-report (legacy)', [
                'subject' => "Cywise : Rapport d'audit",
                'title' => "Rapport d'audit",
                'content' => $this->bodyHtml,
            ])
            ->faking(true);
    }
}
