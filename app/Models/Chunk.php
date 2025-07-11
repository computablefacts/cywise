<?php

namespace App\Models;

use App\Traits\HasTenant;
use Baril\Sqlout\Searchable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

/**
 * @property int id
 * @property Carbon created_at
 * @property Carbon updated_at
 * @property int collection_id
 * @property int file_id
 * @property ?string url
 * @property ?int page
 * @property string text
 * @property boolean is_embedded
 * @property boolean is_deleted
 * @property int created_by
 */
class Chunk extends Model
{
    use HasFactory, HasTenant, Searchable;

    protected $table = 'cb_chunks';

    protected $fillable = [
        'collection_id',
        'file_id',
        'url',
        'page',
        'text',
        'is_embedded',
        'is_deleted',
        'created_by',
    ];

    protected $casts = [
        'page' => 'integer',
        'is_embedded' => 'boolean',
        'is_deleted' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $weights = [
        'section' => 2,
        'subsection' => 3,
        'subsubsection' => 4,
        'text' => 1,
    ];

    public function toSearchableArray()
    {
        $lang = $this->language();
        $lines = explode("\n", $this->text);
        $section = '';
        $subsection = '';
        $subsubsection = '';
        $text = '';

        foreach ($lines as $line) {
            $line = trim($line);
            if (preg_match('/^#\s(.+)$/', $line, $matches)) {
                $section = trim($matches[1]);
            } elseif (preg_match('/^##\s(.+)$/', $line, $matches)) {
                $subsection = trim($matches[1]);
            } elseif (preg_match('/^###\s(.+)$/', $line, $matches)) {
                $subsubsection = trim($matches[1]);
            } else {
                $text .= ($line . "\n");
            }
        }
        return [
            'section' => $lang . ":" . $section,
            'subsection' => $lang . ":" . $subsection,
            'subsubsection' => $lang . ":" . $subsubsection,
            'text' => $lang . ":" . $text,
        ];
    }

    public function shouldBeSearchable(): bool
    {
        return !$this->is_deleted;
    }

    public function isEmbedded(): bool
    {
        return $this->is_embedded;
    }

    public function collection(): HasOne
    {
        return $this->hasOne(Collection::class, 'id', 'collection_id');
    }

    public function file(): HasOne
    {
        return $this->hasOne(File::class, 'id', 'file_id');
    }

    public function tags(): HasMany
    {
        return $this->hasMany(ChunkTag::class, 'chunk_id', 'id');
    }

    public function vectors(): HasMany
    {
        return $this->hasMany(Vector::class, 'chunk_id', 'id');
    }

    public function language()
    {
        /** @var Collection $collection */
        $collection = $this->collection()->first();
        $suffix = Str::substr($collection->name, Str::length($collection->name) - 4, 4);

        if ($collection && Str::startsWith($suffix, 'lg')) {
            return Str::substr($suffix, Str::length($suffix) - 2, 2);
        }
        return '';
    }
}
