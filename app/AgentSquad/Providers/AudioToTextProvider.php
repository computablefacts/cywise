<?php

namespace App\AgentSquad\Providers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/** @deprecated */
class AudioToTextProvider extends AbstractProvider
{
    public static function provide(string $url, string $lang = 'fr'): string
    {
        $audioBase64 = self::downloadAndEncode($url);

        if (!$audioBase64) {
            return '';
        }

        $before = microtime(true);

        try {
            $response = self::callDeepInfra($audioBase64, $lang);
            $answer = $response['text'] ?? '';
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            $answer = null;
        }

        $after = microtime(true);

        if (isset($answer)) {
            self::traceSuccess('audio-to-text', $before, $after);
            return $answer;
        }

        self::traceError('audio-to-text', $before, $after);
        return '';
    }

    private static function downloadAndEncode(string $url)
    {
        try {
            $response = Http::get($url);

            if ($response->failed()) {
                Log::error("Failed to download file from URL: {$url}", [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                return null;
            }

            return base64_encode($response->body());
        } catch (\Exception $e) {
            Log::error("Error encoding audio to base64: {$e->getMessage()}", [
                'url' => $url,
                'exception' => $e
            ]);
            return null;
        }
    }

    private static function callDeepInfra(string $audio, string $lang, int $timeoutInSeconds = 60): array
    {
        return self::post(
            config('towerify.deepinfra.api') . '/../inference/openai/whisper-large-v3-turbo',
            config('towerify.deepinfra.api_key'),
            $audio,
            $lang,
            $timeoutInSeconds
        );
    }

    private static function post(string $url, string $bearer, string $audio, string $lang, int $timeoutInSeconds = 60): array
    {
        try {

            $payload = [
                'audio' => $audio,
                'lang' => $lang,
                'text' => '',
            ];

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$bearer}",
                'Accept' => 'application/json',
            ])
                ->timeout($timeoutInSeconds > 0 ? $timeoutInSeconds : 60)
                ->post($url, $payload);

            if ($response->successful()) {
                $json = $response->json();
                Log::debug($json);
                return $json;
            }
            Log::error($response->body());
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
        return [];
    }
}
