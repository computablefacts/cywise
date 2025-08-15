<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int id
 * @property Carbon created_at
 * @property Carbon updated_at
 * @property string key
 * @property ?string value
 * @property boolean is_encrypted
 *
 * When calling AppConfig::create or AppConfig::updateOrCreate ensure that the is_encrypted flag is set first in the
 * array. Otherwise, the value will not be encrypted by AppConfig::value.
 *
 * ==================================================
 * = KO
 * ==================================================
 * <pre>
 *  AppConfig::updateOrCreate(['key' => 'app.name'], [
 *      'key' => 'app.name',
 *      'value' => 'Cywise',
 *      'is_encrypted' => true,
 *  ]);
 * </pre>
 * ==================================================
 * = OK
 * ==================================================
 * <pre>
 *  AppConfig::updateOrCreate(['key' => 'app.name'], [
 *      'is_encrypted' => true,
 *      'key' => 'app.name',
 *      'value' => 'Cywise',
 *  ]);
 * </pre>
 */
class AppConfig extends Model
{
    use HasFactory;

    protected $table = 'app_config';

    protected $fillable = [
        'key',
        'value',
        'is_encrypted',
    ];

    protected $casts = [
        'is_encrypted' => 'boolean',
    ];

    protected $hidden = [
        'value'
    ];

    protected function value(): Attribute
    {
        return Attribute::make(
            get: fn(?string $value) => $this->is_encrypted ? cywise_unhash($value) : $value,
            set: fn(?string $value) => $this->is_encrypted ? cywise_hash($value) : $value,
        );
    }
}
