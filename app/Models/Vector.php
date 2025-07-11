<?php

namespace App\Models;

use App\Traits\HasTenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * @property int id
 * @property Carbon created_at
 * @property Carbon updated_at
 * @property int collection_id
 * @property int file_id
 * @property int chunk_id
 * @property string locale
 * @property string hypothetical_question
 * @property array embedding
 * @property int created_by
 */
class Vector extends Model
{
    use HasFactory, HasTenant;

    protected $table = 'cb_vectors';

    protected $fillable = [
        'collection_id',
        'file_id',
        'chunk_id',
        'locale',
        'hypothetical_question',
        'embedding',
        'created_by',
    ];

    protected $casts = [
        'embedding' => 'array', // TODO : does it work for MariaDb's vector data type?
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public static function isSupportedByMariaDb(): bool
    {
        return \Illuminate\Support\Facades\Cache::remember("is_vector_supported", 7 * 24 * 60, function () {
            try {
                $version = DB::select('SELECT VERSION() as version')[0]->version;

                if (!str_contains(strtolower($version), 'mariadb')) {
                    Log::warning('The current database is not MariaDB');
                    return false;
                }

                preg_match('/(\d+\.\d+)/', $version, $matches);
                $version = floatval($matches[1] ?? 0);

                if ($version >= 11.7) {
                    return true;
                }

                Log::warning('MariaDB must be at least 11.7 to support vectors. Current version is ' . $version);
                return false;

            } catch (\Exception $e) {
                Log::warning($e->getMessage());
                return false;
            }
        });
    }

    public function collection(): HasOne
    {
        return $this->hasOne(Collection::class, 'id', 'collection_id');
    }

    public function file(): HasOne
    {
        return $this->hasOne(File::class, 'id', 'file_id');
    }

    public function chunk(): HasOne
    {
        return $this->hasOne(Chunk::class, 'id', 'chunk_id');
    }
}
