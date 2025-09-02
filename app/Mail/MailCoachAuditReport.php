<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Spatie\MailcoachMailer\Concerns\UsesMailcoachMail;

class MailCoachAuditReport extends Mailable
{
    use Queueable, SerializesModels, UsesMailcoachMail;

    private string $htmlContent;

    public static function sendEmail(AuditReport $report): void
    {
        try {
            Mail::mailer('mailcoach')
                ->to(Auth::user()->email)
                ->send(new MailCoachAuditReport($report->render()));
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(string $body)
    {
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
            ->mailcoachMail('raw-html', [
                'subject' => "Cywise : Rapport d'audit",
                'title' => "Rapport d'audit",
                'content' => $this->htmlContent,
            ])
            ->faking(true);
    }
}
