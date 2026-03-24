<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * @property int id
 * @property Carbon created_at
 * @property Carbon updated_at
 * @property string name
 * @property Carbon deletion_scheduled_at
 * @property bool cleanup
 */
class Tenant extends Model
{
    use HasFactory;

    private const LOGO_DISK = 'images-s3';
    private const LOGO_DIRECTORY = 'tenants/logos';
    private const LOGO_EXTENSIONS = ['svg', 'png', 'webp', 'jpg', 'jpeg'];

    protected $fillable = [
        'name',
        'deletion_scheduled_at',
        'cleanup',
    ];

    protected $casts = [
        'deletion_scheduled_at' => 'datetime',
        'cleanup' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function logoFileBasename(): string
    {
        return Str::of($this->name)
            ->ascii()
            ->slug('-')
            ->value();
    }

    public function logoPath(string $extension): string
    {
        return sprintf('%s/%s.%s', self::LOGO_DIRECTORY, $this->logoFileBasename(), $extension);
    }

    public function customLogoPath(): ?string
    {
        $disk = Storage::disk(self::LOGO_DISK);

        foreach (self::LOGO_EXTENSIONS as $extension) {
            $path = $this->logoPath($extension);

            if ($disk->exists($path)) {
                return $path;
            }
        }

        return null;
    }

    public function customLogoUrl(): ?string
    {
        $path = $this->customLogoPath();

        if ($path === null) {
            return null;
        }

        return Storage::disk(self::LOGO_DISK)->url($path);
    }

    public function logoUrl(): string
    {
        $url = $this->customLogoUrl();

        if ($url !== null) {
            return $url;
        }

        return asset('cywise/img/cywise.png');
    }
}
