<?php

namespace App\Mail;

use App\Enums\HoneypotCloudProvidersEnum;
use App\Enums\HoneypotCloudSensorsEnum;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Spatie\MailcoachMailer\Concerns\UsesMailcoachMail;

class HoneypotRequested extends Mailable
{
    use Queueable, SerializesModels, UsesMailcoachMail;

    public int $id;
    public HoneypotCloudSensorsEnum $sensor;
    public HoneypotCloudProvidersEnum $provider;
    public string $dns;
    public string $user;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(int $id, HoneypotCloudSensorsEnum $sensor, HoneypotCloudProvidersEnum $provider, string $dns, string $user)
    {
        $this->id = $id;
        $this->sensor = $sensor;
        $this->provider = $provider;
        $this->dns = $dns;
        $this->user = $user;
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
            ->from(config('towerify.freshdesk.from_email'), 'Support')
            ->mailcoachMail('honeypot-requested', [
                'subject' => "Cywise : Honeypot requested by {$this->user}",
                'title' => "Honeypot requested by {$this->user}",
                'id' => $this->id,
                'cloud_provider' => $this->provider->value,
                'cloud_sensor' => $this->sensor->value,
                'dns' => $this->dns,
            ])
            ->faking(!app()->environment('prod', 'production'));
    }

    /**
     * Build standard (local Blade template).
     */
    protected function buildStandard(): self
    {
        return $this
            ->from(config('towerify.freshdesk.from_email'), 'Support')
            ->subject("Cywise : Honeypot requested by {$this->user}")
            ->view('emails.honeypot_requested', [
                'subject' => "Cywise : Honeypot requested by {$this->user}",
                'title' => "Honeypot requested by {$this->user}",
                'id' => $this->id,
                'cloud_provider' => $this->provider->value,
                'cloud_sensor' => $this->sensor->value,
                'dns' => $this->dns,
            ]);
    }
}
