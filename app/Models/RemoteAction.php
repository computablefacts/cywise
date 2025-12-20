<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int id
 * @property string name
 * @property string description
 * @property string url
 * @property array schema
 * @property array headers
 * @property array payload_template
 * @property ?array response_template
 * @property ?\Illuminate\Support\Carbon created_at
 * @property ?\Illuminate\Support\Carbon updated_at
 */
class RemoteAction extends Model
{
    use HasFactory;

    protected $table = 'cb_remote_actions';

    protected $fillable = [
        'name',
        'description',
        'url',
        'headers',
        'schema',
        'payload_template',
        'response_template',

    ];

    protected $casts = [
        'schema' => 'array',
        'headers' => 'array',
        'payload_template' => 'array',
        'response_template' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
