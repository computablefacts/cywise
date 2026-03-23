<?php

namespace App\AgentSquad\Providers;

use App\AgentSquad\Assistants\ChunkAssistant;
use App\AgentSquad\Vectors\MemoryVectorStore;
use App\Enums\LanguageEnum;
use App\Models\Chunk;
use App\Models\Vector;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ChunksProvider
{
    private Collection $collections;
    private int $limit = 8;
    private LanguageEnum $lang = LanguageEnum::FRENCH;
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
    public function provide(): Collection
    {
        if ($this->collections->isEmpty()) {
            return collect();
        }

        $key = 'chunks_provider_' . md5($this->collections->pluck('id')->implode('_') . "{$this->lang->value}:" . implode('_', $this->keywords) . ":{$this->text}");

        // TODO : take the collection priority into account
        return \Cache::remember($key, now()->addDays(7), function () {
            return $this->fullTextSearch()
                ->merge($this->vectorSearch())
                ->groupBy(fn(Chunk $chunk) => $chunk->text)
                ->map(fn(Collection $group) => $group->sortByDesc('_score')->first()) // the higher the better
                ->values() // associative array => array
                ->sortByDesc('_score')
                ->take($this->limit);
        });
    }

    private function fullTextSearch(): Collection
    {
        if (empty($this->keywords)) {
            return collect();
        }

        /** @var array<string> $keywords */
        $keywords = $this->combine($this->keywords, 5);
        /** @var Collection<Chunk> $chunks */
        $chunks = collect();

        foreach ($keywords as $k) {
            $chunks = $chunks->merge(
                Chunk::search("{$this->lang->value}:{$k}")
                    ->whereIn('collection_id', $this->collections->pluck('id'))
                    ->take($this->limit)
                    ->get()
            );
        }
        return $chunks;
    }

    private function vectorSearch(): Collection
    {
        if (empty($this->text)) {
            return collect();
        }

        $embedding = ChunkAssistant::use()->withChunk($this->text)->embedding();

        if (Vector::isSupportedByMariaDb()) {

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
                $chunk->_score = $vector->similarity;
                return $chunk;
            });
        }

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
}