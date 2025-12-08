<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property int id
 * @property Carbon created_at
 * @property Carbon updated_at
 * @property int ynh_ossec_policy_id
 * @property int uid
 * @property string title
 * @property string description
 * @property string rationale
 * @property string impact
 * @property string remediation
 * @property array references
 * @property array compliance
 * @property array requirements
 * @property string rule
 */
class YnhOssecCheck extends Model
{
    use HasFactory;

    protected $table = 'ynh_ossec_checks';

    protected $fillable = [
        'ynh_ossec_policy_id',
        'uid',
        'title',
        'description',
        'rationale',
        'impact',
        'remediation',
        'references',
        'compliance',
        'requirements',
        'rule',
    ];

    protected $casts = [
        'uid' => 'integer',
        'references' => 'array',
        'compliance' => 'array',
        'requirements' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function policy(): BelongsTo
    {
        return $this->belongsTo(YnhOssecPolicy::class, 'ynh_ossec_policy_id', 'id');
    }

    public function frameworks(): array
    {
        return collect($this->compliance)
            ->flatMap(fn(array $compliance) => array_keys($compliance))
            ->map(fn(string $framework) => Str::upper(Str::replace('_', ' ', $framework)))
            ->unique()
            ->sort()
            ->values()
            ->toArray();
    }

    public function hasMitreTactics(): bool
    {
        return in_array('MITRE TACTICS', $this->frameworks());
    }

    public function mitreTactics(): array
    {
        return collect($this->compliance)
            ->flatMap(fn(array $compliance) => $compliance['mitre_tactics'] ?? [])
            ->values()
            ->toArray();
    }

    public function hasMitreTechniques(): bool
    {
        return in_array('MITRE TECHNIQUES', $this->frameworks());
    }

    public function mitreTechniques()
    {
        return collect($this->compliance)
            ->flatMap(fn(array $compliance) => $compliance['mitre_techniques'] ?? [])
            ->map(fn(string $technic) => Str::replace('.', '/', $technic))
            ->values()
            ->toArray();
    }

    public function hasMitreMitigations(): bool
    {
        return in_array('MITRE MITIGATIONS', $this->frameworks());
    }

    public function mitreMitigations()
    {
        return collect($this->compliance)
            ->flatMap(fn(array $compliance) => $compliance['mitre_mitigations'] ?? [])
            ->values()
            ->toArray();
    }

    public function getCywiseLink(): string
    {
        return app_url() . "/home?tab=sca"
            . "&policy=" . $this->policy->uid
            . "&check=" . $this->uid;
    }

    public function requirementsWithCywiseLink(): array
    {
        return array_merge($this->requirements, ['cywise_link' => $this->getCywiseLink()]);
    }

    public function requirementsWithCywiseLinkJson(): string
    {
        return json_encode($this->requirementsWithCywiseLink());
    }
}
