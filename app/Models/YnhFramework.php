<?php

namespace App\Models;

use App\Helpers\Snippet;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * @property int id
 * @property Carbon created_at
 * @property Carbon updated_at
 * @property string name
 * @property string description
 * @property string locale
 * @property string copyright
 * @property string version
 * @property string provider
 * @property string file
 */
class YnhFramework extends Model
{
    use HasFactory;

    protected $table = 'ynh_frameworks';

    protected $fillable = [
        'name',
        'description',
        'locale',
        'copyright',
        'version',
        'provider',
        'file',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function collectionName(): string
    {
        /** @var Auth $user */
        $user = Auth::user();
        return Str::lower("{$this->provider}{$user->tenant_id}lg{$this->locale}");
    }

    public function collection(): ?Collection
    {
        return Collection::where('is_deleted', false)
            ->where('name', $this->collectionName())
            ->first();
    }

    public function file(): ?File
    {
        $collection = $this->collection();
        return $collection ? File::where('is_deleted', false)
            ->where('collection_id', $collection->id)
            ->where('name', trim(basename($this->file, '.jsonl')))
            ->where('extension', 'jsonl')
            ->first() : null;
    }

    public function loaded(): bool
    {
        return $this->file() !== null;
    }

    public function path(): string
    {
        return database_path($this->file);
    }

    public function tree(): array
    {
        $tree = [];
        $jsonStream = gzopen($this->path(), 'rb');

        if ($jsonStream === false) {
            throw new \Exception("Failed to open json file for streaming : {$this->path()}");
        }
        while (($line = gzgets($jsonStream)) !== false) {

            $obj = json_decode(trim($line), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error("JSON decoding error : " . json_last_error_msg());
                continue;
            }
            if (isset($obj['tags'])) {
                $levelCur = &$tree;
                foreach ($obj['tags'] as $tag) {
                    if (!isset($levelCur[$tag])) {
                        $levelCur[$tag] = [];
                    }
                    $levelCur = &$levelCur[$tag];
                }
                $levelCur[] = $obj['text'];
            } else {
                Log::error("Missing tags : {$this->path()} {$line}");
            }
        }
        gzclose($jsonStream);
        return $tree;
    }

    public function highlights(array $words): \Illuminate\Support\Collection
    {
        $words = collect($words)->map(fn(string $word) => trim(Snippet::normalize($word)))->toArray();
        return collect($this->blocks())
            ->filter(fn(string $block) => Snippet::extract($words, Snippet::normalize($block), true)->isNotEmpty())
            ->map(fn(string $block) => preg_replace_callback('/(' . implode('|', array_map('preg_quote', $words)) . ')/i', function ($matches) {
                return '<b>' . $matches[0] . '</b>';
            }, Snippet::normalize($block)));
    }

    public function html(): string
    {
        $text = '';
        $tree = $this->tree();
        $generateIndentedText = function (array $tree, int $level = 0, $uid = null) use (&$generateIndentedText, &$text) {
            if (array_is_list($tree)) {
                $text .= "<div class=\"collapse\" id=\"$uid\"><div style=\"display:grid;\"><div class=\"overflow-auto\"><p>";
            }
            foreach ($tree as $key => $value) {
                $indentation = str_repeat('  ', $level);
                if (is_array($value)) {
                    $uid = Str::random(10);
                    if (array_is_list($value)) {
                        $text .= "<li>{$indentation} <a data-bs-toggle=\"collapse\" href=\"#$uid\" class=\"text-decoration-none\">{$key}</a><ul class=\"ul-small-padding\">";
                    } else {
                        $text .= "<li>{$indentation} {$key}<ul class=\"ul-small-padding\">";
                    }
                    $generateIndentedText($value, $level + 1, $uid);
                    $text .= "</ul></li>";
                } else {
                    $value = Str::replace("\n", "</p><p>", $value);
                    $text .= "<p>$value</p>";
                }
            }
            if (array_is_list($tree)) {
                $text .= "</p></div></div></div>";
            }
        };
        $generateIndentedText($tree);
        return "<ul class=\"ul-small-padding\">$text</ul>";
    }

    public function blocks(): array
    {
        $text = '';
        $blocks = [];
        $tree = $this->tree();
        $generateIndentedText = function (array $tree, int $level = 0, &$titles = []) use (&$generateIndentedText, &$text, &$blocks) {
            foreach ($tree as $key => $value) {
                $indentation = str_repeat('#', $level + 1);
                if (is_array($value)) {
                    $titles[] = "\n{$indentation} {$key}\n";
                    if (array_is_list($value)) {
                        $text = implode('', $titles) . $text;
                    }
                    $generateIndentedText($value, $level + 1, $titles);
                    array_pop($titles);
                } else {
                    $value = Str::replace("\n", "\n\n", $value);
                    $text .= "\n{$value}\n";
                }
            }
            if (array_is_list($tree)) {
                $blocks[] = $text;
                $text = '';
            }
        };
        $generateIndentedText($tree);
        return $blocks;
    }
}
