<?php

namespace App\Models;

use App\Traits\HasTenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int id
 * @property Carbon created_at
 * @property Carbon updated_at
 * @property string hash
 * @property string tag
 * @property int created_by
 */
class AssetTagHash extends Model
{
    use HasFactory, HasTenant;

    protected $table = 'am_assets_tags_hashes';

    protected $fillable = [
        'hash',
        'tag',
        'created_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
