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

    public static function sendEmail(): void
    {
        try {
            Mail::mailer('mailcoach')
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
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $dns = Str::lower(Str::random(4) . '-' . Str::random(4) . '-' . Str::random(4));
        $secret = Str::lower(Str::random(24));
        return $this
            ->from(config('towerify.freshdesk.from_email'), 'Support')
            ->mailcoachMail('performa-requested', [
                'subject' => "Cywise : Performa requested by {$this->user}",
                'title' => "Performa requested by {$this->user}",
                'dns' => "{$dns}.cywise.io",
                'secret' => $secret,
                'id' => $this->id,
            ])
            ->faking(app()->environment('local', 'dev'));
    }
}
