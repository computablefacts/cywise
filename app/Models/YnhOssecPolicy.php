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
            if ($platform === 'unix') {
                return OsqueryPlatformEnum::POSIX;
            }

            $platform = empty($platform) ? null : OsqueryPlatformEnum::tryFrom($platform);
            if ($platform) {
                return $platform;
            }
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

    public function supportsPlatform(OsqueryPlatformEnum $platform): bool
    {
        return match ($this->platform()) {
            OsqueryPlatformEnum::ALL => true,
            OsqueryPlatformEnum::POSIX => in_array($platform, [
                OsqueryPlatformEnum::DARWIN,
                OsqueryPlatformEnum::LINUX,
                OsqueryPlatformEnum::POSIX,
                OsqueryPlatformEnum::UBUNTU,
                OsqueryPlatformEnum::CENTOS,
                OsqueryPlatformEnum::GENTOO,
            ], true),
            OsqueryPlatformEnum::LINUX => in_array($platform, [
                OsqueryPlatformEnum::LINUX,
                OsqueryPlatformEnum::UBUNTU,
                OsqueryPlatformEnum::CENTOS,
                OsqueryPlatformEnum::GENTOO,
            ], true),
            null => false,
            default => $this->platform() === $platform,
        };
    }

    public function agentPayload(): array
    {
        $rules = $this->checks()
            ->orderBy('uid')
            ->get()
            ->each(fn (YnhOssecCheck $check) => $check->setRelation('policy', $this))
            ->map(fn (YnhOssecCheck $check) => $check->agentPayload())
            ->values()
            ->toArray();

        return [
            'uid' => $this->uid,
            'name' => $this->name,
            'revision' => hash('sha256', json_encode([
                'uid' => $this->uid,
                'rules' => array_column($rules, 'revision'),
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)),
            'rules' => $rules,
        ];
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
        return Str::contains($this->name, ['CentOS', 'CentOs'], true);
    }
}
