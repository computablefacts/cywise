<?php

namespace App\Models;

use App\Enums\AssetTypesEnum;
use App\Traits\HasTenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * @property int id
 * @property Carbon created_at
 * @property Carbon updated_at
 * @property string asset
 * @property AssetTypesEnum type
 * @property ?string tld
 * @property ?string prev_scan_id
 * @property ?string cur_scan_id
 * @property ?string next_scan_id
 * @property ?string discovery_id
 * @property bool is_monitored
 * @property int created_by
 * @property int ynh_trial_id
 */
class Asset extends Model
{
    use HasFactory, HasTenant;

    protected $table = 'am_assets';

    protected $fillable = [
        'asset',
        'type',
        'tld',
        'prev_scan_id',
        'cur_scan_id',
        'next_scan_id',
        'discovery_id',
        'is_monitored',
        'created_by',
        'ynh_trial_id',
    ];

    protected $casts = [
        'type' => AssetTypesEnum::class,
        'is_monitored' => 'boolean',
        'ynh_trial_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function trial(): BelongsTo
    {
        return $this->belongsTo(YnhTrial::class, 'ynh_trial_id', 'id');
    }

    public function isDns(): bool
    {
        return $this->type === AssetTypesEnum::DNS;
    }

    public function isIp(): bool
    {
        return $this->type === AssetTypesEnum::IP;
    }

    public function isRange(): bool
    {
        return $this->type === AssetTypesEnum::RANGE;
    }

    public function tld(): ?string
    {
        if ($this->isDns()) {
            if ($this->tld) {
                return $this->tld;
            }

            // Url need to start with protocol
            if (substr($this->asset, 0, 7) === "http://" || substr($this->asset, 0, 8) === "https://") {
                $UrlElements = explode(".", parse_url($this->asset, PHP_URL_HOST));
            } else {
                $UrlElements = explode(".", parse_url('http://' . $this->asset, PHP_URL_HOST));
            }

            $lastElement = end($UrlElements);
            $prevElement = prev($UrlElements);

            if (count($UrlElements) >= 2) {
                $this->tld = mb_strtolower($prevElement . '.' . $lastElement);
                $this->save();
                return $this->tld;
            }
        }
        return null;
    }

    public function tags(): HasMany
    {
        return $this->hasMany(AssetTag::class, 'asset_id', 'id');
    }

    public function ports(): Builder
    {
        return Port::select('am_ports.*')
            ->join('am_scans', 'am_scans.id', '=', 'am_ports.scan_id')
            ->join('am_assets', 'am_assets.cur_scan_id', '=', 'am_scans.ports_scan_id')
            ->where('am_assets.id', $this->id);
    }

    public function alertsWithCriticalityHigh(): Builder
    {
        return $this->alerts()->whereIn('level', ['Critical', 'High']);
    }

    public function alertsWithCriticalityMedium(): Builder
    {
        return $this->alerts()->where('level', 'Medium');
    }

    public function alertsWithCriticalityLow(): Builder
    {
        return $this->alerts()->where('level', 'Low');
    }

    public function alertsWithCriticalityUnverified(): Builder
    {
        return $this->alerts()->where('level', 'High (unverified)');
    }

    public function alerts(): Builder
    {
        $hiddenAlerts = HiddenAlert::whereNotNull('uid')
            ->orWhereNotNull('type')
            ->orWhereNotNull('title')
            ->get();
        $hiddenUids = $hiddenAlerts
            ->filter(fn(HiddenAlert $marker) => !empty($marker->uid))
            ->map(fn(HiddenAlert $marker) => $marker->uid);
        $hiddenTypes = $hiddenAlerts
            ->filter(fn(HiddenAlert $marker) => !empty($marker->type))
            ->map(fn(HiddenAlert $marker) => $marker->type);
        $hiddenTitles = $hiddenAlerts
            ->filter(fn(HiddenAlert $marker) => !empty($marker->title))
            ->map(fn(HiddenAlert $marker) => addslashes($marker->title));

        $ifUids = $hiddenUids->isEmpty() ? 'false' : "am_alerts.uid IN ('{$hiddenUids->join("','")}')";
        $ifTypes = $hiddenTypes->isEmpty() ? 'false' : "am_alerts.type IN ('{$hiddenTypes->join("','")}')";
        $ifTitles = $hiddenTitles->isEmpty() ? 'false' : "am_alerts.title IN ('{$hiddenTitles->join("','")}')";
        $case = "CASE WHEN {$ifUids} OR {$ifTypes} OR {$ifTitles} THEN true ELSE false END AS is_hidden";

        return Alert::select('am_alerts.*', DB::raw($case))
            ->join('am_ports', 'am_ports.id', '=', 'am_alerts.port_id')
            ->join('am_scans', 'am_scans.id', '=', 'am_ports.scan_id')
            ->join('am_assets', 'am_assets.cur_scan_id', '=', 'am_scans.ports_scan_id')
            ->where('am_assets.id', $this->id);
    }

    /**
     * This method name is misleading because it does not tells you if all running scans on this asset completed.
     * It tells you what are the Scan objects for the last successful scan of this asset.
     *
     * @return Collection a list of scans
     * @deprecated
     */
    public function scanCompleted(): Collection
    {
        return Scan::where('asset_id', $this->id)
            ->where('ports_scan_id', $this->cur_scan_id)
            ->get();
    }

    public function scanInProgress(): Collection
    {
        return Scan::where('asset_id', $this->id)
            ->where('ports_scan_id', $this->next_scan_id)
            ->get();
    }
}
