<?php

namespace App\AgentSquad\Providers;

use App\AgentSquad\Vectors\MemoryVectorStore;
use App\Models\Chunk;
use App\Models\Vector;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ChunksProvider2
{
    /** @return Collection<Chunk> */
    public static function provide(Collection $collections, string $language, string $input, int $take = 8): Collection
    {
        if ($collections->isEmpty() || empty($language) || empty($input)) {
            return collect();
        }
        try {
            $start = microtime(true);
            if (Vector::isSupportedByMariaDb()) {
                $embedding = json_encode(EmbeddingsProvider::provide($input)?->embedding() ?? []);
                $chunks = collect(DB::select("
                    SELECT DISTINCT
                      chunk_id, 
                      (1 - VEC_DISTANCE_COSINE(VEC_FromText('{$embedding}'), embedding)) AS similarity
                    FROM cb_vectors
                    WHERE locale = '{$language}'
                    AND collection_id IN ({$collections->pluck('id')->implode(',')})
                    ORDER BY VEC_DISTANCE_COSINE(VEC_FromText('{$embedding}'), embedding)
                    LIMIT {$take}
                "))->map(function (object $vector) {
                    /** @var Chunk $chunk */
                    $chunk = Chunk::findOrFail($vector->chunk_id);
                    $chunk->_score = $vector->similarity;
                    return $chunk;
                });
            } else {
                $embedding = EmbeddingsProvider::provide($input)?->embedding() ?? [];
                $vectorStore = new MemoryVectorStore($take);
                $vectorStore->addVectors(collect(DB::select("
                    SELECT DISTINCT *
                    FROM cb_vectors
                    WHERE locale = '{$language}'
                    AND collection_id IN ({$collections->pluck('id')->implode(',')})
                "))->map(fn(object $vector) => new \App\AgentSquad\Vectors\Vector(
                    $vector->hypothetical_question,
                    json_decode($vector->embedding, true),
                    ['chunk_id' => $vector->chunk_id]
                ))->toArray());
                $chunks = collect($vectorStore->search($embedding))
                    ->map(function (array $vector) {
                        /** @var Chunk $chunk */
                        $chunk = Chunk::findOrFail($vector['vector']->metadata('chunk_id'));
                        $chunk->_score = $vector['similarity'];
                        return $chunk;
                    });
            }
            $stop = microtime(true);
            Log::debug("[CHUNKS_PROVIDER_2] Search for '$input' took " . ((int)ceil($stop - $start)) . " seconds and returned {$chunks->count()} results");
            return $chunks;
        } catch (\Exception $e) {
            Log::debug("[CHUNKS_PROVIDER_2] Search for '$input' failed");
            Log::error($e->getMessage());
            return collect();
        }
    }
}