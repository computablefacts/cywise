<?php

namespace App\AgentSquad\Providers;

use App\Models\Chunk;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ChunksProvider extends AbstractProvider
{
    /** @return Collection<Chunk> */
    public static function provide(Collection $collections, string $language, string $keywords, int $take = 50): Collection
    {
        if ($collections->isEmpty() || empty($language) || empty($keywords)) {
            return collect();
        }
        return \Cache::remember('chunks_provider_' . md5($collections->pluck('id')->implode('_') . "{$language}{$keywords}"), now()->addDays(7), function () use ($collections, $language, $keywords, $take) {

            $before = microtime(true);

            try {
                $chunks = Chunk::search("{$language}:{$keywords}")
                    ->whereIn('collection_id', $collections->pluck('id'))
                    ->take($take)
                    ->get();
            } catch (\Exception $e) {
                Log::error($e->getMessage());
                $chunks = null;
            }

            $after = microtime(true);

            if (isset($chunks)) {
                self::traceSuccess('chunks', $before, $after);
                return $chunks;
            }

            self::traceError('chunks', $before, $after);
            return collect();
        });
    }
}