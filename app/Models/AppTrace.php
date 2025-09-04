<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int id
 * @property Carbon created_at
 * @property Carbon updated_at
 * @property string verb
 * @property string endpoint
 * @property ?string procedure
 * @property ?string method
 * @property int duration_in_ms
 * @property boolean failed
 * @property ?int user_id
 */
class AppTrace extends Model
{
    use HasFactory;

    protected $table = 'app_traces';

    protected $fillable = [
        'verb',
        'endpoint',
        'procedure',
        'method',
        'duration_in_ms',
        'failed',
        'user_id'
    ];

    protected $casts = [
        'failed' => 'boolean',
    ];
}
