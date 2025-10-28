<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property int id
 * @property Carbon created_at
 * @property Carbon updated_at
 * @property string name
 * @property Carbon first_contact
 * @property Carbon last_contact
 */
class Attacker extends Model
{
    use HasFactory;

    protected $table = 'am_attackers';

    protected $fillable = [
        'name',
        'first_contact',
        'last_contact',
    ];

    protected $casts = [
        'first_contact' => 'datetime',
        'last_contact' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function events(): HasMany
    {
        return $this->hasMany(HoneypotEvent::class, 'attacker_id', 'id');
    }

    public function humans(): HasMany
    {
        return $this->events()->where('human', true);
    }

    public function targeted(): HasMany
    {
        return $this->events()->where('targeted', true);
    }

    public function curatedWordlist(): HasMany
    {
        return $this->events()->where('event', 'curated_wordlist');
    }

    public function curatedPasswords(): HasMany
    {
        return $this->events()->where('event', 'manual_actions_password_targeted');
    }

    public function knownIpAddresses(): Collection
    {
        return $this->events()
            ->get()
            ->pluck('ip')
            ->unique()
            ->sort()
            ->values();
    }

    public function usedTools(): Collection
    {
        return $this->events()
            ->where('event', 'tool_detected')
            ->get()
            ->pluck('details')
            ->unique()
            ->sort()
            ->values();
    }

    public function testedCves(): Collection
    {
        return $this->events()
            ->where('event', 'cve_tested')
            ->get()
            ->pluck('details')
            ->unique()
            ->sort()
            ->values();
    }

    public function aggressiveness(int $totalNumberOfEvents): string
    {
        $numberOfEvents = $this->events()->count();
        $ratio = $numberOfEvents / $totalNumberOfEvents * 100;
        if ($ratio <= 33) {
            return 'low';
        }
        if ($ratio <= 66) {
            return 'medium';
        }
        return 'high';
    }
}
