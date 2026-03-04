<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

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
}
