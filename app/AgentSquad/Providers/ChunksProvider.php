<?php

namespace App\AgentSquad\Providers;

use App\Models\Chunk;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ChunksProvider
{
    /** @return Collection<Chunk> */
    public static function provide(Collection $collections, string $language, string $keywords, int $take = 50): Collection
    {
        if ($collections->isEmpty() || empty($language) || empty($keywords)) {
            return collect();
        }
        return \Cache::remember('chunks_provider_' . md5($collections->pluck('id')->implode('_') . "{$language}{$keywords}"), 7 * 24 * 60, function () use ($collections, $language, $keywords, $take) {
            try {
                $start = microtime(true);
                $chunks = Chunk::search("{$language}:{$keywords}")
                    ->whereIn('collection_id', $collections->pluck('id'))
                    ->take($take)
                    ->get();
                $stop = microtime(true);
                Log::debug("[CHUNKS_PROVIDER] Search for '{$language}:{$keywords}' took " . ((int)ceil($stop - $start)) . " seconds and returned {$chunks->count()} results");
                return $chunks;
            } catch (\Exception $e) {
                Log::debug("[CHUNKS_PROVIDER] Search for '{$language}:{$keywords}' failed");
                Log::error($e->getMessage());
                return collect();
            }
        });
    }
}