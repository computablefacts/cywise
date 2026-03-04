<?php

namespace App\AgentSquad\Providers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LlmsProvider extends AbstractProvider
{
    public static function provideJson(string|array $messages, ?string $model = null, int $timeoutInSeconds = 60): object
    {
        $matches = null;
        $string = self::provide($messages, $model, $timeoutInSeconds);
        preg_match_all('/(?:```json\s*)?(.*)(?:\s*```)?/s', $string, $matches);
        $raw = '{' . Str::after(Str::beforeLast(Str::trim($matches[1][0]), '}'), '{') . '}'; //  deal with "}<｜end▁of▁sentence｜>"
        return (object)[
            'raw' => $raw,
            'parsed' => json_decode($raw, true),
        ];
    }

    public static function provide(string|array $messages, ?string $model = null, int $timeoutInSeconds = 60): string
    {
        $before = microtime(true);

        $model = $model ?? 'Qwen/Qwen3-Next-80B-A3B-Instruct';

        if (is_string($messages)) {
            $messages = [[
                'role' => 'user',
                'content' => $messages
            ]];
        }
        try {
            $response = self::callDeepInfra($messages, $model, $timeoutInSeconds);
            $answer = $response['choices'][0]['message']['content'] ?? '';
            $answer = Str::trim(preg_replace('/<think>.*?<\/think>/s', '', $answer));
            $answer = Str::trim(Str::replace(['[OUTPUT]', '[/OUTPUT]'], '', $answer, false));
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            $answer = null;
        }

        $after = microtime(true);

        if (isset($answer)) {
            self::traceSuccess('llms/' . Str::lower($model), $before, $after);
            return $answer;
        }

        self::traceError('llms/' . Str::lower($model), $before, $after);
        return '';
    }

    private static function callDeepInfra(array $messages, string $model, int $timeoutInSeconds = 60): array
    {
        return self::post(
            config('towerify.deepinfra.api') . '/chat/completions', config('towerify.deepinfra.api_key', 'fake_bearer'), $messages, $model, $timeoutInSeconds);
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