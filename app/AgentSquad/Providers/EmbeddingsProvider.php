<?php

namespace App\AgentSquad\Providers;

use App\AgentSquad\Vectors\Vector;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EmbeddingsProvider extends AbstractProvider
{
    public static function provide(string $text, array $metadata = []): ?Vector
    {
        return \Cache::remember('embeddings_provider_' . md5($text), now()->addDays(7), function () use ($text, $metadata) {

            $before = microtime(true);

            try {
                $embedding = self::callDeepInfra($text)['data'][0]['embedding'] ?? [];
                $vector = new Vector($text, $embedding, $metadata);
            } catch (\Exception $e) {
                Log::error($e->getMessage());
                $vector = null;
            }

            $after = microtime(true);

            if (isset($vector)) {
                self::traceSuccess('embeddings', $before, $after);
                return $vector;
            }

            self::traceError('embeddings', $before, $after);
            return null;
        });
    }

    private static function callDeepInfra(string $text, ?string $model = null): array
    {
        return self::post(config('towerify.deepinfra.api') . '/embeddings', config('towerify.deepinfra.api_key'), $text, $model ?? 'BAAI/bge-m3-multi');
    }

    private static function post(string $url, string $bearer, string $text, string $model): array
    {
        try {

            $payload = [
                'model' => $model,
                'input' => $text,
                'encoding_format' => 'float',
            ];

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$bearer}",
                'Accept' => 'application/json',
            ])
                ->timeout(60)
                ->post($url, $payload);

            if ($response->successful()) {
                $json = $response->json();
                // Log::debug($json);
                return $json;
            }
            Log::error($response->body());
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
        return [];
    }
}