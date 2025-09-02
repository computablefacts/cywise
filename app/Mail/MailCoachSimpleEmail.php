<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Spatie\MailcoachMailer\Concerns\UsesMailcoachMail;

class MailCoachSimpleEmail extends Mailable
{
    use Queueable, SerializesModels, UsesMailcoachMail;

    private string $emailSubject;
    private string $htmlContent;

    public static function sendEmail(string $subject, string $body, ?string $to = null): void
    {
        try {
            Mail::mailer('mailcoach')
                ->to($to ?? config('towerify.freshdesk.to_email'))
                ->send(new MailCoachSimpleEmail($subject, $body));
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(string $subject, string $body)
    {
        $this->emailSubject = $subject;
        $this->htmlContent = $body;
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
            ->mailcoachMail('honeypot-requested', [
                'subject' => $this->emailSubject,
                'content' => $this->htmlContent,
            ])
            ->faking(true);
    }
}
