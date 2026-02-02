<?php

namespace App\AgentSquad\Providers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LlmsProvider
{
    public static function provide(string|array $messages, ?string $model = null, int $timeoutInSeconds = 60): string
    {
        if (is_string($messages)) {
            $messages = [[
                'role' => 'user',
                'content' => $messages
            ]];
        }
        try {
            $start = microtime(true);
            $response = self::callDeepInfra($messages, $model, $timeoutInSeconds);
            $stop = microtime(true);
            $answer = $response['choices'][0]['message']['content'] ?? '';
            $answer = Str::trim(preg_replace('/<think>.*?<\/think>/s', '', $answer));
            $answer = Str::trim(Str::replace(['[OUTPUT]', '[/OUTPUT]'], '', $answer, false));
            Log::debug("[LLMS_PROVIDER] LLM api call took " . ((int)ceil($stop - $start)) . " seconds");
            return $answer;
        } catch (\Exception $e) {
            Log::debug("[LLMS_PROVIDER] LLM api call failed");
            Log::error($e->getMessage());
            return '';
        }
    }

    private static function callDeepInfra(array $messages, ?string $model = null, int $timeoutInSeconds = 60): array
    {
        return self::post(
            config('towerify.deepinfra.api') . '/chat/completions', config('towerify.deepinfra.api_key'), $messages, $model ?? 'Qwen/Qwen3-Next-80B-A3B-Instruct', $timeoutInSeconds);
    }

    private static function post(string $url, string $bearer, array $messages, string $model, int $timeoutInSeconds = 60): array
    {
        try {

            $payload = [
                'model' => $model,
                'messages' => $messages,
                'temperature' => 0.7,
                'stream' => false,
            ];

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$bearer}",
                'Accept' => 'application/json',
            ])
                ->timeout($timeoutInSeconds > 0 ? $timeoutInSeconds : 60)
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