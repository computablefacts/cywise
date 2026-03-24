<?php

namespace App\Models;

use App\Traits\HasTenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int id
 * @property ?string $thread_id
 * @property Carbon created_at
 * @property Carbon updated_at
 * @property string $input
 * @property ?string $output
 * @property float $elapsed_time_in_seconds
 * @property int created_by
 */
class Trace extends Model
{
    use HasFactory, HasTenant;

    protected $table = 'cb_traces';

    protected $fillable = [
        'thread_id',
        'input',
        'output',
        'elapsed_time_in_seconds',
        'created_by',
    ];

    protected $casts = [
        'elapsed_time_in_seconds' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
