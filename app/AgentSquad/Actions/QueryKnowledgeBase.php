<?php

namespace App\AgentSquad\Actions;

use App\AgentSquad\Answers\AbstractAnswer;
use App\AgentSquad\Answers\FailedAnswer;
use App\AgentSquad\Answers\SuccessfulAnswer;
use App\AgentSquad\Assistants\ChunkAssistant;
use App\AgentSquad\Assistants\TextAssistant;
use App\AgentSquad\Providers\ChunksProvider;
use App\AgentSquad\Providers\MemosProvider;
use App\AgentSquad\Vectors\FileVectorStore;
use App\AgentSquad\Vectors\Vector;
use App\Enums\LanguageEnum;
use App\Http\Procedures\NotesProcedure;
use App\Models\Chunk;
use App\Models\ChunkTag;
use App\Models\File;
use App\Models\User;
use App\Rules\IsValidCollectionName;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class QueryKnowledgeBase extends AbstractAction
{
    protected function schema(): array
    {
        return [
            "type" => "function",
            "function" => [
                "name" => "query_knowledge_base",
                "description" => "Retrieve answers by searching the organization's internal knowledge base—including documents such as PDFs, Word files (DOCX) and audio recordings (WAV, MP3) to quickly locate relevant rules, security policies, procedures, or other institutional information. The action's input must use the same language as the user's input: if the user asks their question in French, the input must be in French; if they ask in English, the input must be in English.",
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
        $result = TextAssistant::use()
            ->withThreadId($threadId)
            ->withTimeout(30 * 60)
            ->withMessagesAndPrompt($messages, 'default_reformulate_question', [
                'QUESTION' => htmlspecialchars($input, ENT_QUOTES, 'UTF-8'),
            ])
            ->structured();
        /** @var string $answer */
        $answer = $result->raw;
        /** @var array $json */
        $json = $result->parsed;

        if (!$json) {
            return new FailedAnswer(__("The answer is not a valid JSON: {$answer}"));
        }
        if (($json['lang'] ?? '') !== 'french' && ($json['lang'] ?? '') !== 'english') {
            return new FailedAnswer(__("The language is unknown: {$answer}"));
        }
        if (empty($json['question_en'] ?? '') && empty($json['question_fr'] ?? '')) {
            return new FailedAnswer(__("The questions are missing: {$answer}"));
        }
        if (empty($json['keywords_en'] ?? []) && empty($json['keywords_fr'] ?? [])) {
            return new FailedAnswer(__("The keywords are missing: {$answer}"));
        }

        // Extract similar questions from ANSSI's dataset
        $anssi = [];

        if (!empty($json['question_fr'] ?? '')) {

            $embedding = ChunkAssistant::use()
                ->withLang(LanguageEnum::FRENCH)
                ->withChunk($json['question_fr'] ?? '')
                ->embedding();

            if (!empty($embedding)) {
                $dir = FileVectorStore::unpack("anssi.zip");
                $vectorStore = new FileVectorStore($dir, 5);
                $anssi = array_values(array_filter($vectorStore->search($embedding), fn(array $vector) => $vector['similarity'] > 0.6));
                $anssi = array_map(function (array $vector, int $index) {
                    /** @var Vector $vec */
                    $vec = $vector['vector'];
                    $question = $vec->text();
                    $answer = preg_replace('/#+/', '', $vec->metadata('answer'));
                    $similarity = $vector['similarity'];
                    return "## Memo A{$index}\n\n**Question:** {$question}\n**Answer:** {$answer}\n**Source:** ANSSI\n**Score:** {$similarity}";
                }, $anssi, array_keys($anssi));
            }
        }

        // Extract similar questions from Rowden's Cybersecurity QAA dataset
        $rowden = [];

        if (!empty($json['question_en'] ?? '')) {

            $embedding = ChunkAssistant::use()
                ->withLang(LanguageEnum::ENGLISH)
                ->withChunk($json['question_en'] ?? '')
                ->embedding();

            if (!empty($embedding)) {
                $dir = FileVectorStore::unpack("rowden_cybersecurityqaa.zip");
                $vectorStore = new FileVectorStore($dir, 5);
                $rowden = array_values(array_filter($vectorStore->search($embedding), fn(array $vector) => $vector['similarity'] > 0.6));
                $rowden = array_map(function (array $vector, int $index) {
                    /** @var Vector $vec */
                    $vec = $vector['vector'];
                    $question = $vec->text();
                    $answer = $vec->metadata('answer');
                    $source = $vec->metadata('source');
                    $source = empty($source) ? 'n/a' : $source;
                    $similarity = $vector['similarity'];
                    return "## Memo R{$index}\n\n**Question:** {$question}\n**Answer:** {$answer}\n**Source:** {$source}\n**Score:** {$similarity}";
                }, $rowden, array_keys($rowden));
            }
        }

        // Fill context & answer question
        $memos = empty($collection) ?
            MemosProvider::use()
                ->withScope(NotesProcedure::SCOPE_IS_CYBERBUDDY)
                ->withUser($user)
                ->provide() :
            '';
        $result = $this->loadChunks($user, $json['question_en'] ?? '', $json['question_fr'] ?? '', $json['keywords_en'] ?? [], $json['keywords_fr'] ?? [], $collection);
        $question = $json['lang'] === 'english' ?
            $json['question_en'] :
            ($json['lang'] === 'french' ? $json['question_fr'] : $input);
        $answer = TextAssistant::use()
            ->withThreadId($threadId)
            ->withMessagesAndPrompt($messages, 'default_answer_question', [
                'LANGUAGE' => $json['lang'],
                'NOTES' => $result['chunks'],
                'MEMOS' => $memos . "\n\n" . implode("\n\n", $anssi) . "\n\n" . implode("\n\n", $rowden),
                'QUESTION' => $question,
            ])
            ->text();

        return new SuccessfulAnswer($this->enhanceWithSources(Str::trim(Str::replace('I_DONT_KNOW', '', strip_tags($answer)))));
    }

    private function loadChunks(User $user, string $questionEn, string $questionFr, array $keywordsEn, array $keywordsFr, ?string $collection = null): array
    {
        $result = [];
        $start = microtime(true);

        $chunksEn = ChunksProvider::use()
            ->withLang(LanguageEnum::ENGLISH)
            ->withCollections($this->englishCollections($collection))
            ->withKeywords($keywordsEn)
            ->withText($questionEn)
            ->withLimit(20)
            ->provide();

        $stop = microtime(true);
        $result['chunks_en'] = [
            'count' => $chunksEn->count(),
            'elapsed_time_in_seconds' => (int)ceil($stop - $start),
        ];
        $start = microtime(true);

        $chunksFr = ChunksProvider::use()
            ->withLang(LanguageEnum::FRENCH)
            ->withCollections($this->frenchCollections($collection))
            ->withKeywords($keywordsFr)
            ->withText($questionFr)
            ->withLimit(20)
            ->provide();

        $stop = microtime(true);
        $result['chunks_fr'] = [
            'count' => $chunksFr->count(),
            'elapsed_time_in_seconds' => (int)ceil($stop - $start),
        ];
        $start = microtime(true);

        $chunks = $chunksEn
            ->merge($chunksFr)
            ->groupBy(fn(Chunk $chunk) => $chunk->text)
            ->map(fn(Collection $group) => $group->sortByDesc('_score')->first()) // the higher the better
            ->values() // associative array => array
            ->sortByDesc('_score')
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
        $result['chunks_merged'] = [
            'count' => $chunks->count(),
            'elapsed_time_in_seconds' => (int)ceil($stop - $start),
        ];
        $result['chunks'] = $chunks->join("\n\n");

        return $result;
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

    private function enhanceWithSources(string $answer): string
    {
        $matches = [];
        // Remove [[Memo ...]]
        $answer = preg_replace('/\[\[Memo\s+.*?]]/i', '', $answer);
        // Replace [[Note 1234]] by [[1234]]
        $answer = preg_replace('/\[\[Note\s+/i', '[[', $answer);
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
            if (!$chunk) {
                $answer = Str::replace($ref, "", $answer);
            } else {
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
        }
        ksort($references);
        if (!empty($references)) {
            $answer = "{$answer}<br><br><b>Sources :</b><ul>" . collect($references)->values()->join("") . "</ul>";
        }
        return Str::replace(["\n\n", "\n-"], "<br>", $answer);
    }
}
