<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $leak_date
 * @property string|null $leak_type
 * @property string $email
 * @property string|null $website
 * @property string|null $password
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Leak extends Model
{
    use HasTenant;

    protected $table = 'am_leaks';

    protected $fillable = [
        'created_by',
        'leak_date',
        'leak_type',
        'email',
        'website',
        'password',
    ];

    protected $casts = [
        'leak_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
