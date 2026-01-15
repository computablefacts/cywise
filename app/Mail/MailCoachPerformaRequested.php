<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Spatie\MailcoachMailer\Concerns\UsesMailcoachMail;

class MailCoachPerformaRequested extends Mailable
{
    use Queueable, SerializesModels, UsesMailcoachMail;

    private int $id;
    private string $user;
    private string $dns;
    private string $secret;

    public static function sendEmail(): void
    {
        try {
            Mail::mailer()
                ->to(config('towerify.freshdesk.to_email'))
                ->send(new MailCoachPerformaRequested(Auth::user()->id, Auth::user()->email));
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(int $id, string $user)
    {
        $this->id = $id;
        $this->user = $user;
        $this->dns = 'a' . Str::lower(Str::random(3) . '-' . Str::random(4) . '-' . Str::random(4));;
        $this->secret = Str::lower(Str::random(24));
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
            default     => $this->buildStandard(), // Tous les autres
        };
    }

    /**
     * Build for Mailcoach (remote template).
     */
    protected function buildMailcoach(): self
    {
        return $this
            ->from(config('towerify.freshdesk.from_email'), 'Support')
            ->mailcoachMail('performa-requested', [
                'subject' => "Cywise : Performa requested by {$this->user}",
                'title' => "Performa requested by {$this->user}",
                'dns' => "{$this->dns}.cywise.io",
                'secret' => $this->secret,
                'id' => $this->id,
            ])
            ->faking(! app()->environment('prod', 'production'));
    }

    /**
     * Build standard (local Blade template).
     */
    protected function buildStandard(): self
    {
        return $this
            ->from(config('towerify.freshdesk.from_email'), 'Support')
            ->subject("Cywise : Performa requested by {$this->user}")
            ->view('emails.performa_requested', [
                'subject' => "Cywise : Performa requested by {$this->user}",
                'title' => "Performa requested by {$this->user}",
                'dns' => "{$this->dns}.cywise.io",
                'secret' => $this->secret,
                'id' => $this->id,
            ]);
    }
}
