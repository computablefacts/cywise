<?php

namespace App\AgentSquad\Providers;

use App\AgentSquad\Assistants\ChunkAssistant;
use App\AgentSquad\Vectors\MemoryVectorStore;
use App\Enums\LanguageEnum;
use App\Models\Chunk;
use App\Models\Vector;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ChunksProvider extends AbstractProvider
{
    private Collection $collections;
    private int $limit = 8;
    private LanguageEnum $lang = LanguageEnum::FRENCH;
    /** @var array<array<string>> */
    private array $keywords = [];
    private string $text = '';

    public static function use(): ChunksProvider
    {
        return new ChunksProvider();
    }

    public function withCollections(Collection $collections): ChunksProvider
    {
        $this->collections = $collections;
        return $this;
    }

    public function withLang(LanguageEnum $lang): ChunksProvider
    {
        $this->lang = $lang;
        return $this;
    }

    public function withLimit(int $limit): ChunksProvider
    {
        $this->limit = $limit <= 0 ? 8 : $limit;
        return $this;
    }

    /** @param array<array<string>> $keywords */
    public function withKeywords(array $keywords): ChunksProvider
    {
        $this->keywords = $keywords;
        return $this;
    }

    public function withText(string $text): ChunksProvider
    {
        $this->text = $text;
        return $this;
    }

    /** @return Collection<Chunk> */
    protected function provide2(): Collection
    {
        if ($this->collections->isEmpty()) {
            return collect();
        }

        // TODO : take the collection priority into account

        $fullTextChunks = $this->fullTextSearch();
        $vectorChunks = $this->vectorSearch();

        // Create a union of all unique chunks (keyed by text)
        $uniqueChunks = [];

        foreach ($fullTextChunks as $chunk) {
            if (!isset($uniqueChunks[$chunk->text])) {
                $uniqueChunks[$chunk->text] = [
                    'chunk' => $chunk,
                    'fulltext_score' => $chunk->_score ?? 0.0,
                    'vector_score' => 0.0,
                ];
            } else {
                $uniqueChunks[$chunk->text]['fulltext_score'] = max($uniqueChunks[$chunk->text]['fulltext_score'], $chunk->_score ?? 0.0);
            }
        }
        foreach ($vectorChunks as $chunk) {
            if (!isset($uniqueChunks[$chunk->text])) {
                $uniqueChunks[$chunk->text] = [
                    'chunk' => $chunk,
                    'fulltext_score' => 0.0,
                    'vector_score' => $chunk->_score ?? 0.0,
                ];
            } else {
                $uniqueChunks[$chunk->text]['vector_score'] = max($uniqueChunks[$chunk->text]['vector_score'], $chunk->_score ?? 0.0);
            }
        }
        return collect($uniqueChunks)
            ->map(function (array $data) {
                /** @var Chunk $chunk */
                $chunk = $data['chunk'];
                $chunk->_score = (0.3 * $data['fulltext_score']) + (0.7 * $data['vector_score']);
                return $chunk;
            })
            ->sortByDesc('_score')
            ->values()
            ->take($this->limit);
    }

    private function fullTextSearch(): Collection
    {
        if (empty($this->keywords)) {
            return collect();
        }

        $collectionsKeyPart = md5($this->collections->pluck('id')->sort()->implode(','));
        /** @var array<string> $keywords */
        $keywords = $this->combine($this->keywords, 5);
        /** @var Collection<Chunk> $chunks */
        $chunks = collect();

        foreach ($keywords as $k) {
            $chunkz = \Cache::remember("fulltext:{$this->lang->value}:{$k}:{$collectionsKeyPart}", now()->addDays(7), function () use ($k) {
                return Chunk::search("{$this->lang->value}:{$k}")
                    ->whereIn('collection_id', $this->collections->pluck('id'))
                    ->get();
            });
            $chunks = $chunks->merge($chunkz)->sortByDesc('_score')->take($this->limit);
        }
        return $this->minMaxScaler($chunks);
    }

    private function vectorSearch(): Collection
    {
        if (empty($this->text)) {
            return collect();
        }

        $collectionsKeyPart = md5($this->collections->pluck('id')->sort()->implode(','));
        $textKeyPart = md5($this->text);
        $embedding = ChunkAssistant::use()->withChunk($this->text)->embedding();

        if (Vector::isSupportedByMariaDb()) {
            $chunks = \Cache::remember("vectors:{$this->lang->value}:{$textKeyPart}:{$collectionsKeyPart}", now()->addDays(7), function () use ($embedding) {

                $embedding = json_encode($embedding);

                return collect(DB::select("
                    SELECT DISTINCT
                      chunk_id, 
                      (1 - VEC_DISTANCE_COSINE(VEC_FromText('{$embedding}'), embedding)) AS similarity
                    FROM cb_vectors
                    WHERE locale = '{$this->lang->value}'
                    AND collection_id IN ({$this->collections->pluck('id')->implode(',')})
                    ORDER BY VEC_DISTANCE_COSINE(VEC_FromText('{$embedding}'), embedding)
                    LIMIT {$this->limit}
                "))->map(function (object $vector) {
                    /** @var Chunk $chunk */
                    $chunk = Chunk::findOrFail($vector->chunk_id);
                    $chunk->_score = (float)$vector->similarity;
                    return $chunk;
                });
            });
            return $this->minMaxScaler($chunks);
        }

        $chunks = \Cache::remember("vectors:{$this->lang->value}:{$textKeyPart}:{$collectionsKeyPart}", now()->addDays(7), function () use ($embedding) {

            $vectorStore = new MemoryVectorStore($this->limit);
            $vectorStore->addVectors(collect(DB::select("
                SELECT DISTINCT *
                FROM cb_vectors
                WHERE locale = '{$this->lang->value}'
                AND collection_id IN ({$this->collections->pluck('id')->implode(',')})
            "))->map(fn(object $vector) => new \App\AgentSquad\Vectors\Vector(
                $vector->hypothetical_question,
                json_decode($vector->embedding, true),
                ['chunk_id' => $vector->chunk_id]
            ))->toArray());

            return collect($vectorStore->search($embedding))
                ->map(function (array $vector) {
                    /** @var Chunk $chunk */
                    $chunk = Chunk::findOrFail($vector['vector']->metadata('chunk_id'));
                    $chunk->_score = $vector['similarity'];
                    return $chunk;
                });
        });
        return $this->minMaxScaler($chunks);
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

    private function minMaxScaler(Collection $chunks): Collection
    {
        if ($chunks->isEmpty()) {
            return $chunks;
        }

        $min = $chunks->min('_score');
        $max = $chunks->max('_score');

        if ($max == $min) {
            return $chunks->map(function (Chunk $chunk) {
                $chunk->_score = 1.0;
                return $chunk;
            });
        }
        return $chunks->map(function (Chunk $chunk) use ($min, $max) {
            $chunk->_score = ($chunk->_score - $min) / ($max - $min);
            return $chunk;
        });
    }
}