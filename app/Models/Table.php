<?php

namespace App\Models;

use App\Traits\HasTenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int id
 * @property Carbon created_at
 * @property Carbon updated_at
 * @property string name
 * @property string description
 * @property string query
 * @property boolean copied
 * @property boolean deduplicated
 * @property boolean updatable
 * @property array credentials
 * @property array schema
 * @property int nb_rows
 * @property ?string last_error
 * @property ?string last_warning
 * @property int created_by
 * @property Carbon started_at
 * @property Carbon finished_at
 * @property boolean bypass_missing_columns_warning
 * @property boolean bypass_rowcount_warning
 */
class Table extends Model
{
    use HasFactory, HasTenant;

    protected $table = 'cb_tables';

    protected $fillable = [
        'name',
        'description',
        'copied',
        'deduplicated',
        'created_by',
        'last_error',
        'last_warning',
        'started_at',
        'finished_at',
        'updatable',
        'schema',
        'credentials',
        'nb_rows',
        'query',
        'bypass_missing_columns_warning',
        'bypass_rowcount_warning',
    ];

    protected $casts = [
        'bypass_missing_columns_warning' => 'boolean',
        'bypass_rowcount_warning' => 'boolean',
        'copied' => 'boolean',
        'deduplicated' => 'boolean',
        'updatable' => 'boolean',
        'schema' => 'array',
        'nb_rows' => 'integer',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $hidden = ['credentials'];

    protected function credentials(): Attribute
    {
        return Attribute::make(
            get: fn(string $value) => json_decode(cywise_unhash($value), true),
            set: fn(array $value) => cywise_hash(json_encode($value))
        );
    }

    public function status(): string
    {
        if ($this->last_error) {
            return 'Error: ' . $this->last_error;
        }
        if ($this->last_warning) {
            return 'Warning: ' . $this->last_warning;
        }
        if ($this->started_at && !$this->finished_at) {
            return 'Importing...';
        }
        if ($this->started_at && $this->finished_at) {
            return 'Imported';
        }
        return '';
    }
}
