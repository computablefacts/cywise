<?php

namespace App\AgentSquad\Providers;

use App\AgentSquad\Vectors\Vector;
use App\Helpers\EmbeddingProvider;
use Illuminate\Support\Facades\Log;

class EmbeddingsProvider
{
    public static function provide(string $text, array $metadata = []): ?Vector
    {
        return \Cache::remember('embeddings_provider_' . md5($text), 7 * 24 * 60, function () use ($text, $metadata) {
            try {
                $start = microtime(true);
                $provider = new EmbeddingProvider(EmbeddingProvider::DEEP_INFRA);
                $embedding = $provider->execute($text)['data'][0]['embedding'] ?? [];
                $vector = new Vector($text, $embedding, $metadata);
                $stop = microtime(true);
                Log::debug("[EMBEDDINGS_PROVIDER] Computing embeddings took " . ((int)ceil($stop - $start)) . " seconds");
                return $vector;
            } catch (\Exception $e) {
                Log::debug("[EMBEDDINGS_PROVIDER] Computing embeddings failed");
                Log::error($e->getMessage());
                return null;
            }
        });
    }
}