<?php

namespace App\Models;

use App\Enums\OsqueryPlatformEnum;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property int id
 * @property Carbon created_at
 * @property Carbon updated_at
 * @property string uid
 * @property string name
 * @property string description
 * @property array references
 * @property array requirements
 */
class YnhOssecPolicy extends Model
{
    use HasFactory;

    protected $table = 'ynh_ossec_policies';

    protected $fillable = [
        'uid',
        'name',
        'description',
        'references',
        'requirements',
    ];

    protected $casts = [
        'references' => 'array',
        'requirements' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function platform(): ?OsqueryPlatformEnum
    {
        if (Str::startsWith($this->uid, 'cywise_')) {
            $platform = Str::afterLast($this->uid, '_');
            return empty($platform) ? null : OsqueryPlatformEnum::tryFrom($platform);
        }
        if ($this->isWindows()) {
            return OsqueryPlatformEnum::WINDOWS;
        }
        if ($this->isDebian()) {
            return OsqueryPlatformEnum::LINUX;
        }
        if ($this->isUbuntu()) {
            return OsqueryPlatformEnum::UBUNTU;
        }
        if ($this->isCentOs()) {
            return OsqueryPlatformEnum::CENTOS;
        }
        return null;
    }

    public function checks(): HasMany
    {
        return $this->hasMany(YnhOssecCheck::class, 'ynh_ossec_policy_id', 'id');
    }

    public function isWindows(): bool
    {
        return Str::contains($this->name, ['Microsoft', 'Windows', 'IIS'], true);
    }

    public function isDebian(): bool
    {
        return Str::contains($this->name, ['Debian', 'Nginx', 'Apache', 'Unix'], true);
    }

    public function isUbuntu(): bool
    {
        return Str::contains($this->name, ['Ubuntu', 'Nginx', 'Apache', 'Unix'], true);
    }

    public function isCentOs(): bool
    {
        return Str::contains($this->name, ['CentOs'], true);
    }
}
