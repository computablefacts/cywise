<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int id
 * @property Carbon created_at
 * @property Carbon updated_at
 * @property int scan_id
 * @property string hostname
 * @property string ip
 * @property int port
 * @property string protocol
 * @property ?string country
 * @property ?string service
 * @property ?string product
 * @property ?string hosting_service_description
 * @property ?string hosting_service_registry
 * @property ?string hosting_service_asn
 * @property ?string hosting_service_cidr
 * @property ?string hosting_service_country_code
 * @property ?string hosting_service_date
 * @property ?bool ssl
 * @property bool closed
 */
class Port extends Model
{
    use HasFactory;

    protected $table = 'am_ports';

    protected $fillable = [
        'scan_id',
        'hostname',
        'ip',
        'port',
        'protocol',
        'country',
        'hosting_service_description',
        'hosting_service_registry',
        'hosting_service_asn',
        'hosting_service_cidr',
        'hosting_service_country_code',
        'hosting_service_date',
        'service',
        'product',
        'ssl',
        'closed',
    ];

    protected $casts = [
        'ssl' => 'boolean',
        'closed' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function tags(): HasMany
    {
        return $this->hasMany(PortTag::class, 'port_id', 'id');
    }

    public function screenshot(): HasOne
    {
        return $this->hasOne(Screenshot::class, 'port_id', 'id');
    }

    public function scan(): BelongsTo
    {
        return $this->belongsTo(Scan::class, 'scan_id', 'id');
    }
}
