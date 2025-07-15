<?php

namespace App\AgentSquad\Actions;

use App\AgentSquad\AbstractAction;
use App\AgentSquad\Answers\AbstractAnswer;
use App\AgentSquad\Answers\FailedAnswer;
use App\AgentSquad\Answers\SuccessfulAnswer;
use App\AgentSquad\Providers\ChunksProvider;
use App\AgentSquad\Providers\ChunksProvider2;
use App\AgentSquad\Providers\LlmsProvider;
use App\AgentSquad\Providers\PromptsProvider;
use App\Enums\RoleEnum;
use App\Models\Chunk;
use App\Models\ChunkTag;
use App\Models\TimelineItem;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CyberBuddy extends AbstractAction
{
    private const string MODEL = 'deepseek-ai/DeepSeek-R1-0528-Turbo';

    static function schema(): array
    {
        return [
            "type" => "function",
            "function" => [
                "name" => "query_knowledge_base",
                "description" => "Answer questions related to cybersecurity guidelines or procedures. This includes inquiries about best practices, frameworks (such as ANSSI, NIST, OWASP, NIS2, DORA), or the Information Systems Security Policy (ISSP).",
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
        $prompt = PromptsProvider::provide('default_reformulate_question', [
            'QUESTION' => htmlspecialchars($input, ENT_QUOTES, 'UTF-8'),
        ]);
        $messages[] = [
            'role' => RoleEnum::USER->value,
            'content' => $prompt,
        ];
        $answer = LlmsProvider::provide($messages, self::MODEL, 3 * 60);
        array_pop($messages);

        $matches = null;
        preg_match_all('/(?:```json\s*)?(.*)(?:\s*```)?/s', $answer, $matches);
        $answer = Str::trim($matches[1][0]);
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

        $notes = $this->loadNotes($user);
        $chunks = $this->loadChunks($user, $json['question_en'] ?? '', $json['question_fr'] ?? '', $json['keywords_en'] ?? [], $json['keywords_fr'] ?? []);
        $prompt = PromptsProvider::provide('default_answer_question', [
            'LANGUAGE' => $json['lang'],
            'NOTES' => $notes,
            'MEMOS' => $chunks,
            'QUESTION' => $json['lang'] === 'english' ?
                $json['question_en'] :
                ($json['lang'] === 'french' ? $json['question_fr'] : $input),
        ]);
        $messages[] = [
            'role' => RoleEnum::USER->value,
            'content' => $prompt,
        ];
        $answer = LlmsProvider::provide($messages, self::MODEL, 120);
        array_pop($messages);

        return new SuccessfulAnswer($answer);
    }

    private function loadNotes(User $user): string
    {
        $start = microtime(true);
        $notes = TimelineItem::fetchNotes($user->id, null, null, 0)
            ->map(function (TimelineItem $note) {
                $attributes = $note->attributes();
                $subject = $attributes['subject'] ?? 'Unknown subject';
                $body = $attributes['body'] ?? '';
                return "## {$note->timestamp->format('Y-m-d H:i:s')}\n\n### {$subject}\n\n{$body}";
            });
        $stop = microtime(true);
        Log::debug("[LOAD_NOTES] Loading notes took " . ((int)ceil($stop - $start)) . " seconds and returned {$notes->count()} results");
        return $notes->join("\n\n");
    }

    private function loadChunks(User $user, string $questionEn, string $questionFr, array $keywordsEn, array $keywordsFr): string
    {
        $start = microtime(true);
        $fullTextSearchEn = $this->fullTextSearch($user, 'en', $keywordsEn);
        $fullTextSearchFr = $this->fullTextSearch($user, 'fr', $keywordsFr);
        $stop = microtime(true);
        $nbResults = $fullTextSearchEn->count() + $fullTextSearchFr->count();
        Log::debug("[LOAD_CHUNKS] Full-text search for '{$questionEn}' took " . ((int)ceil($stop - $start)) . " seconds and returned {$nbResults} results");
        $start = microtime(true);
        $vectorSearchEn = $this->vectorSearch($user, 'en', $questionEn);
        $vectorSearchFr = $this->vectorSearch($user, 'fr', $questionFr);
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

                return "## Chunk {$chunk->id}\n\n{$text}\n\n**Tags:** {$tags}\n**Score:** {$chunk->{'_score'}}";
            });
        $stop = microtime(true);
        Log::debug("[LOAD_CHUNKS] Loading chunks for '{$questionEn}' took " . ((int)ceil($stop - $start)) . " seconds and returned {$chunks->count()} results");
        return $chunks->join("\n\n");
    }

    /** @return Collection<Chunk> */
    private function fullTextSearch(User $user, string $lang, array $input): Collection
    {
        /** @var array<string> $keywords */
        $keywords = $this->combine($input);
        /** @var Collection<Chunk> $chunks */
        $chunks = collect();
        foreach ($keywords as $k) {
            if ($lang === 'en') {
                $chunkz = ChunksProvider::provide($this->englishCollections(), 'en', $k, 5);
            } else if ($lang === 'fr') {
                $chunkz = ChunksProvider::provide($this->frenchCollections(), 'fr', $k, 5);
            } else {
                $chunkz = collect();
            }
            $chunks = $chunks->merge($chunkz);
        }
        return $chunks;
    }

    /** @return Collection<Chunk> */
    private function vectorSearch(User $user, string $lang, string $input): Collection
    {
        if ($lang === 'en') {
            return ChunksProvider2::provide($this->englishCollections(), 'en', $input, 4);
        }
        if ($lang === 'fr') {
            return ChunksProvider2::provide($this->frenchCollections(), 'fr', $input, 4);
        }
        return collect();
    }

    private function englishCollections(): Collection
    {
        return \App\Models\Collection::query()
            ->where('cb_collections.is_deleted', false)
            ->where(function ($query) {
                $query->where('cb_collections.name', 'like', "%lgen") // see YnhFramework::collectionName
                ->orWhere('cb_collections.name', 'not like', '%lg%');
            })
            ->orderBy('cb_collections.priority')
            ->orderBy('cb_collections.name')
            ->get();
    }

    private function frenchCollections(): Collection
    {
        return \App\Models\Collection::query()
            ->where('cb_collections.is_deleted', false)
            ->where(function ($query) {
                $query->where('cb_collections.name', 'like', "%lgfr") // see YnhFramework::collectionName
                ->orWhere('cb_collections.name', 'not like', '%lg%');
            })
            ->orderBy('cb_collections.priority')
            ->orderBy('cb_collections.name')
            ->get();
    }

    private function combine(array $arrays): array
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
        return array_map(fn(array $combination) => implode(" ", $combination), $combinations);
    }
}
