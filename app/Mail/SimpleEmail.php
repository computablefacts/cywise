<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Spatie\MailcoachMailer\Concerns\UsesMailcoachMail;

class SimpleEmail extends Mailable
{
    use Queueable, SerializesModels, UsesMailcoachMail;

    public string $emailFrom;
    public string $emailSubject;
    public string $htmlTitle;
    public string $htmlBody;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(string $subject, string $htmlTitle, string $htmlBody, ?string $from = null)
    {
        $this->emailSubject = $subject;
        $this->htmlTitle = $htmlTitle;
        $this->htmlBody = $htmlBody;
        $this->emailFrom = $from ?? config('towerify.freshdesk.from_email');
    }

    public function __get($name)
    {
        return $this->$name;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return match (config('mail.default')) {
            'mailcoach' => $this->buildMailcoach(),
            default => $this->buildStandard(), // Tous les autres
        };
    }

    /**
     * Build for Mailcoach (remote template).
     */
    protected function buildMailcoach(): self
    {
        return $this
            ->from($this->emailFrom, 'support')
            ->mailcoachMail('default', [
                'subject' => $this->emailSubject,
                'title' => $this->htmlTitle,
                'content' => $this->htmlBody,
            ])
            ->faking(!app()->environment('prod', 'production'));
    }

    /**
     * Build standard (local Blade template).
     */
    protected function buildStandard(): self
    {
        return $this
            ->from($this->emailFrom, 'support')
            ->subject($this->emailSubject)
            ->view('emails.simple_email', [
                'subject' => $this->emailSubject,
                'title' => $this->htmlTitle,
                'content' => $this->htmlBody,
            ]);
    }
}
