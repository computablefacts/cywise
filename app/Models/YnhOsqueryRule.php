<?php

namespace App\Models;

use App\Enums\OsqueryPlatformEnum;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * @property int id
 * @property Carbon created_at
 * @property Carbon updated_at
 * @property string name
 * @property string description
 * @property string query
 * @property ?string version
 * @property int interval
 * @property bool snapshot
 * @property OsqueryPlatformEnum platform
 * @property ?string category
 * @property bool enabled
 * @property ?string attck
 * @property bool is_ioc
 * @property double score
 * @property ?string comments
 * @property ?int created_by
 */
class YnhOsqueryRule extends Model
{
    use HasFactory;

    protected $table = 'ynh_osquery_rules';

    protected $fillable = [
        'name',
        'description',
        'version',
        'query',
        'interval',
        'snapshot',
        'platform',
        'category',
        'enabled',
        'attck',
        'is_ioc',
        'score',
        'comments',
        'created_by',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'snapshot' => 'boolean',
        'is_ioc' => 'boolean',
        'score' => 'float',
        'platform' => OsqueryPlatformEnum::class,
        'created_by' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function displayName(): string
    {
        return \Auth::user()?->isCywiseAdmin() ? $this->name : Str::after($this->name, 'cywise_');
    }

    public function mitreAttckTactics(): array
    {
        return $this->mitreAttck()->flatMap(fn(YnhMitreAttck $attck) => $attck->tactics)->unique()->sort()->toArray();
    }

    private function mitreAttck(): Collection
    {
        if ($this->attck) {
            $refs = explode(',', $this->attck);
            return YnhMitreAttck::query()->whereIn('uid', $refs)->get();
        }
        return collect();
    }
}
