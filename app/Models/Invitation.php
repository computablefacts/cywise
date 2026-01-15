<?php

namespace App\Models;

use App\Mail\SimpleEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int id
 * @property string email
 * @property string token
 * @property ?int sent_by
 * @property ?int received_by
 * @property Carbon expires_at
 * @property Carbon accepted_at
 * @property Carbon created_at
 * @property Carbon updated_at
 */
class Invitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'token',
        'sent_by',
        'received_by',
        'expires_at',
        'accepted_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public static function generate(string $email, User $sender): self
    {
        $newInvitation = self::create([
            'email' => $email,
            'token' => Str::random(32),
            'expires_at' => now()->addDays(30),
        ]);

        $newInvitation->sender()->associate($sender);
        $newInvitation->save();

        return $newInvitation;
    }

    public function acceptedBy(User $recipient): void
    {
        /** @var User $sender */
        $sender = $this->sender()->first();
        $recipient->tenant_id = $sender->tenant_id;
        $recipient->save();

        $this->recipient()->associate($recipient);
        $this->accepted_at = now();
        $this->save();
    }

    public function alreadyUsed()
    {
        return !is_null($this->accepted_at);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function sendEmail()
    {
        $sender = $this->sender()->first();
        $invitationLink = $this->getLink();

        $subject = "Cywise : invitation à rejoindre {$sender->email}";
        $htmlTitle = "Invitation à rejoindre {$sender->email}";
        $htmlBody = <<<EOT
<a href="mailto:{$sender->email}">{$sender->email}</a> vous invite à le rejoindre sur Cywise.<br/>
<br/>
Cliquez sur ce lien pour créer votre compte : <br/>
<a href="{$invitationLink}">{$invitationLink}</a>
EOT;

        SimpleEmail::sendEmail($subject, $htmlTitle, $htmlBody, $this->email);
    }

    public function getLink()
    {
        return route('auth.register', [
            'invitation' => $this->token,
        ]);
    }
}
