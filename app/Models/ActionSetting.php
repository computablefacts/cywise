<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int id
 * @property string scope_type
 * @property int scope_id
 * @property string action
 * @property boolean enabled
 */
class ActionSetting extends Model
{
    use HasFactory;

    protected $table = 'cb_action_settings';

    protected $fillable = [
        'scope_type',
        'scope_id',
        'action',
        'enabled',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
