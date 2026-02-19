<?php

namespace App\Models;

use App\Traits\HasTenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int id
 * @property Carbon created_at
 * @property Carbon updated_at
 * @property string hash
 * @property ?string domain
 * @property ?array subdomains
 * @property boolean honeypots
 * @property ?string email
 * @property boolean completed
 * @property ?int created_by
 */
class Trial extends Model
{
    use HasFactory, HasTenant;

    protected $table = 'ynh_trials';

    protected $fillable = [
        'hash',
        'domain',
        'subdomains',
        'honeypots',
        'email',
        'completed',
        'created_by',
    ];

    protected $casts = [
        'subdomains' => 'array',
        'honeypots' => 'boolean',
        'completed' => 'boolean',
        'created_by' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class, 'ynh_trial_id', 'id');
    }
}
