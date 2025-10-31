<?php

namespace App\AgentSquad\Actions;

use App\AgentSquad\AbstractAction;
use App\AgentSquad\Answers\AbstractAnswer;
use App\AgentSquad\Answers\FailedAnswer;
use App\AgentSquad\Answers\SuccessfulAnswer;
use App\AgentSquad\Providers\ChunksProvider;
use App\AgentSquad\Providers\ChunksProvider2;
use App\AgentSquad\Providers\LlmsProvider;
use App\AgentSquad\Providers\MemosProvider;
use App\AgentSquad\Providers\PromptsProvider;
use App\Enums\RoleEnum;
use App\Models\Chunk;
use App\Models\ChunkTag;
use App\Models\File;
use App\Models\User;
use App\Rules\IsValidCollectionName;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CyberBuddy extends AbstractAction
{
    private const string MODEL_REFORMULATE = 'Qwen/Qwen3-Next-80B-A3B-Instruct';
    private const string MODEL_ANSWER = 'Qwen/Qwen3-235B-A22B-Thinking-2507';

    static function schema(): array
    {
        return [
            "type" => "function",
            "function" => [
                "name" => "query_knowledge_base",
                "description" => "
                    Answer questions related to cybersecurity guidelines or procedures. 
                    This includes inquiries about best practices, frameworks (such as ANSSI, NIST, OWASP, NIS2, DORA), or the Information Systems Security Policy (ISSP). 
                    The action's input must use the same language as the user: if the user asks their question in French, the input must be in French; if they ask in English, the input must be in English.
                ",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "question" => [
                            "type" => "string",
                            "description" => "A user question related to information security.",
                        ],
                    ],
                    "required" => ["question"],
                    "additionalProperties" => false,
                ],
                "strict" => true,
            ],
        ];
    }

    public function __construct()
    {
        //
    }

    public function execute(User $user, string $threadId, array $messages, string $input): AbstractAnswer
    {
        // Extract collection (if any)
        $collection = Str::before($input, ':');

        if (IsValidCollectionName::test($collection) && \App\Models\Collection::where('name', $collection)->exists()) {
            $input = Str::after($input, ':');
        } else {
            $collection = null;
        }

        // Reformulate question in both english and french
        $prompt = PromptsProvider::provide('default_reformulate_question', [
            'QUESTION' => htmlspecialchars($input, ENT_QUOTES, 'UTF-8'),
        ]);
        $messages[] = [
            'role' => RoleEnum::USER->value,
            'content' => $prompt,
        ];
        $answer = LlmsProvider::provide($messages, self::MODEL_REFORMULATE, 3 * 60);
        array_pop($messages);

        $matches = null;
        preg_match_all('/(?:```json\s*)?(.*)(?:\s*```)?/s', $answer, $matches);
        $answer = '{' . Str::after(Str::beforeLast(Str::trim($matches[1][0]), '}'), '{') . '}'; //  deal with "}<｜end▁of▁sentence｜>"
        $json = json_decode($answer, true);

        if (!$json) {
            return new FailedAnswer("The answer is not a valid JSON: {$answer}");
        }
        if (($json['lang'] ?? '') !== 'french' && ($json['lang'] ?? '') !== 'english') {
            return new FailedAnswer("The language is unknown: {$answer}");
        }
        if (empty($json['question_en'] ?? '') && empty($json['question_fr'] ?? '')) {
            return new FailedAnswer("The questions are missing: {$answer}");
        }
        if (empty($json['keywords_en'] ?? []) && empty($json['keywords_fr'] ?? [])) {
            return new FailedAnswer("The keywords are missing: {$answer}");
        }

        // Fill context & answer question
        $memos = empty($collection) ? MemosProvider::provide($user) : '';
        $chunks = $this->loadChunks($user, $json['question_en'] ?? '', $json['question_fr'] ?? '', $json['keywords_en'] ?? [], $json['keywords_fr'] ?? [], $collection);
        $prompt = PromptsProvider::provide('default_answer_question', [
            'LANGUAGE' => $json['lang'],
            'NOTES' => $chunks,
            'MEMOS' => $memos,
            'QUESTION' => $json['lang'] === 'english' ?
                $json['question_en'] :
                ($json['lang'] === 'french' ? $json['question_fr'] : $input),
        ]);
        $messages[] = [
            'role' => RoleEnum::USER->value,
            'content' => $prompt,
        ];
        $answer = LlmsProvider::provide($messages, self::MODEL_ANSWER, 120);
        array_pop($messages);

        return new SuccessfulAnswer($this->enhanceWithSources(strip_tags($answer)), [], !empty($answer));
    }

    private function loadChunks(User $user, string $questionEn, string $questionFr, array $keywordsEn, array $keywordsFr, ?string $collection = null): string
    {
        $start = microtime(true);
        $fullTextSearchEn = $this->fullTextSearch($user, 'en', $keywordsEn, $collection);
        $fullTextSearchFr = $this->fullTextSearch($user, 'fr', $keywordsFr, $collection);
        $stop = microtime(true);
        $nbResults = $fullTextSearchEn->count() + $fullTextSearchFr->count();
        Log::debug("[LOAD_CHUNKS] Full-text search for '{$questionEn}' took " . ((int)ceil($stop - $start)) . " seconds and returned {$nbResults} results");
        $start = microtime(true);
        $vectorSearchEn = $this->vectorSearch($user, 'en', $questionEn, $collection);
        $vectorSearchFr = $this->vectorSearch($user, 'fr', $questionFr, $collection);
        $stop = microtime(true);
        $nbResults = $vectorSearchEn->count() + $vectorSearchFr->count();
        Log::debug("[LOAD_CHUNKS] Vector search for '{$questionEn}' took " . ((int)ceil($stop - $start)) . " seconds and returned {$nbResults} results");
        $start = microtime(true);
        $chunks = $fullTextSearchEn
            ->merge($fullTextSearchFr)
            ->merge($vectorSearchEn)
            ->merge($vectorSearchFr)
            ->groupBy(fn(Chunk $chunk) => $chunk->text)
            ->map(fn(Collection $group) => $group->sortByDesc('_score')->first()) // the higher the better
            ->values() // associative array => array
            ->sortByDesc('_score')
            ->sortBy('priority')
            ->take(20)
            ->map(function (Chunk $chunk) {

                $text = preg_replace('/^#/m', '###', $chunk->text);

                $tags = ChunkTag::where('chunk_id', '=', $chunk->id)
                    ->orderBy('id')
                    ->get()
                    ->map(fn(ChunkTag $tag) => $tag->tag)
                    ->join(", ");

                $tags = empty($tags) ? 'n/a' : $tags;

                return "## Note {$chunk->id}\n\n{$text}\n\n**Tags:** {$tags}\n**Score:** {$chunk->{'_score'}}";
            });
        $stop = microtime(true);
        Log::debug("[LOAD_CHUNKS] Loading chunks for '{$questionEn}' took " . ((int)ceil($stop - $start)) . " seconds and returned {$chunks->count()} results");
        return $chunks->join("\n\n");
    }

    /** @return Collection<Chunk> */
    private function fullTextSearch(User $user, string $lang, array $input, ?string $collection = null): Collection
    {
        /** @var array<string> $keywords */
        $keywords = $this->combine($input, 5);
        /** @var Collection<Chunk> $chunks */
        $chunks = collect();
        foreach ($keywords as $k) {
            if ($lang === 'en') {
                $chunkz = ChunksProvider::provide($this->englishCollections($collection), 'en', $k, 5);
            } else if ($lang === 'fr') {
                $chunkz = ChunksProvider::provide($this->frenchCollections($collection), 'fr', $k, 5);
            } else {
                $chunkz = collect();
            }
            $chunks = $chunks->merge($chunkz);
        }
        return $chunks;
    }

    /** @return Collection<Chunk> */
    private function vectorSearch(User $user, string $lang, string $input, ?string $collection = null): Collection
    {
        if ($lang === 'en') {
            return ChunksProvider2::provide($this->englishCollections($collection), 'en', $input, 4);
        }
        if ($lang === 'fr') {
            return ChunksProvider2::provide($this->frenchCollections($collection), 'fr', $input, 4);
        }
        return collect();
    }

    private function englishCollections(?string $collection = null): Collection
    {
        return \App\Models\Collection::query()
            ->where('cb_collections.is_deleted', false)
            ->where(function ($query) {
                $query->where('cb_collections.name', 'like', "%lgen") // see YnhFramework::collectionName
                ->orWhere('cb_collections.name', 'not like', '%lg%');
            })
            ->when($collection, fn($query) => $query->where('cb_collections.name', '=', $collection))
            ->orderBy('cb_collections.priority')
            ->orderBy('cb_collections.name')
            ->get();
    }

    private function frenchCollections(?string $collection = null): Collection
    {
        return \App\Models\Collection::query()
            ->where('cb_collections.is_deleted', false)
            ->where(function ($query) {
                $query->where('cb_collections.name', 'like', "%lgfr") // see YnhFramework::collectionName
                ->orWhere('cb_collections.name', 'not like', '%lg%');
            })
            ->when($collection, fn($query) => $query->where('cb_collections.name', '=', $collection))
            ->orderBy('cb_collections.priority')
            ->orderBy('cb_collections.name')
            ->get();
    }

    private function combine(array $arrays, int $sample = -1): array
    {
        if (empty($arrays)) {
            return [];
        }

        /** @var array<array<string>> $combinations */
        $combinations = array_map(fn(string $word) => [$word], $arrays[0]);

        for ($i = 1; $i < count($arrays); $i++) {

            /** @var array<string> $cur */
            $cur = $arrays[$i];
            $new = [];

            foreach ($combinations as $existing) {
                foreach ($cur as $word) {
                    $new[] = array_merge($existing, [$word]);
                }
            }
            $combinations = $new;
        }
        if ($sample > 0) {
            shuffle($combinations);
            $combinations = array_slice($combinations, 0, min(count($combinations), $sample));
        }
        return array_map(fn(array $combination) => implode(" ", $combination), $combinations);
    }

    private function enhanceWithSources(string $answer): string
    {
        $matches = [];
        // Extract: [12] from [[12]] or [[12] and [13]] from [[12],[13]]
        $isOk = preg_match_all("/\[\[\d+]]|\[\[\d+]|\[\d+]]/", $answer, $matches);
        if (!$isOk) {
            return Str::replace(["\n\n", "\n-"], "<br>", $answer);
        }
        $references = [];
        /** @var array $refs */
        $refs = $matches[0];
        foreach ($refs as $ref) {
            $id = Str::replace(['[', ']'], '', $ref);
            /** @var Chunk $chunk */
            $chunk = Chunk::find($id);
            /** @var File $file */
            $file = $chunk?->file()->first();
            $src = $file ? "<a href=\"{$file->downloadUrl()}\" style=\"text-decoration:none;color:black\">{$file->name_normalized}.{$file->extension}</a>, p. {$chunk?->page}" : "";
            if (Str::startsWith($chunk?->text ?? '', 'ESSENTIAL DIRECTIVE')) {
                $color = '#1DD288';
            } else if (Str::startsWith($chunk?->text ?? '', 'STANDARD DIRECTIVE')) {
                $color = '#C5C3C3';
            } else if (Str::startsWith($chunk?->text ?? '', 'ADVANCED DIRECTIVE')) {
                $color = '#FDC99D';
            } else {
                $color = '#F8B500';
            }
            $tt = $chunk?->text ?? '';
            $answer = Str::replace($ref, "<b style=\"color:{$color}\">[{$id}]</b>", $answer);
            $references[$id] = "
<li style=\"padding:0;margin-bottom:0.25rem\">
  <b style=\"color:{$color}\">[{$id}]</b>&nbsp;
  <div class=\"cb-tooltip-list\">
    {$src}
    <span class=\"cb-tooltiptext cb-tooltip-list-top\" style=\"background-color:{$color};color:#444;\">
      {$tt}
    </span>
  </div>
</li>";
        }
        ksort($references);
        $answer = "{$answer}<br><br><b>Sources :</b><ul>" . collect($references)->values()->join("") . "</ul>";
        return Str::replace(["\n\n", "\n-"], "<br>", $answer);
    }
}
