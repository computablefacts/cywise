<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LlmProvider
{
    const string DEEP_INFRA = 'deep_infra';
    const string DEEP_SEEK = 'deep_seek';
    const string OPEN_AI = 'open_ai';
    const string GEMINI = 'gemini';

    protected string $provider;
    protected int $timeoutInSeconds;

    public static function isHyperlink(string $text): bool
    {
        return Str::startsWith(Str::lower($text), ["https://", "http://"]);
    }

    public static function download(string $text, string $country = 'fr'): string
    {
        if (self::isHyperlink($text)) {
            if (config('towerify.scrapfly.api_key')) {
                $news = Http::get('https://api.scrapfly.io/scrape?render_js=true&asp=true&cache=true&cache_ttl=86400&key=' . config('towerify.scrapfly.api_key') . "&country={$country}&url={$text}");
                return json_decode($news, true)['result']['content'];
            }
            if (config('towerify.scraperapi.api_key')) {
                return Http::get('http://api.scraperapi.com?api_key=' . config('towerify.scraperapi.api_key') . '&url=' . $text);
            }
            Log::error('Missing scraper API key!');
            return '';
        }
        return $text;
    }

    public static function cleanSqlQuery(string $query): string
    {
        return Str::rtrim($query, ';');
    }

    public function __construct(string $provider, int $timeoutInSeconds = 60)
    {
        $this->provider = $provider;
        $this->timeoutInSeconds = $timeoutInSeconds > 0 ? $timeoutInSeconds : 60;
    }

    public function execute(string|array $messages, ?string $model = null, array $tools = []): array
    {
        if (is_string($messages)) {
            $messages = [[
                'role' => 'user',
                'content' => $messages
            ]];
        }
        return match ($this->provider) {
            self::DEEP_INFRA => $this->callDeepInfra($messages, $model ?? 'Qwen/Qwen3-Next-80B-A3B-Instruct', 0.7, $tools),
            self::DEEP_SEEK => $this->callDeepSeek($messages, $model ?? 'deepseek-chat', 0.7, $tools),
            self::OPEN_AI => $this->callOpenAi($messages, $model ?? 'gpt-4o', 0.7, $tools),
            self::GEMINI => $this->callGemini($messages, $model ?? 'gemini-2.0-flash', 0.7, $tools),
            default => [],
        };
    }

    private function callDeepInfra(array $messages, string $model, float $temperature, array $tools = []): array
    {
        return $this->post(config('towerify.deepinfra.api') . '/chat/completions', config('towerify.deepinfra.api_key'), $messages, $model, $temperature, $tools);
    }

    private function callDeepSeek(array $messages, string $model, float $temperature, array $tools = []): array
    {
        return $this->post(config('towerify.deepseek.api') . '/chat/completions', config('towerify.deepseek.api_key'), $messages, $model, $temperature, $tools);
    }

    private function callOpenAi(array $messages, string $model, float $temperature, array $tools = []): array
    {
        return $this->post(config('towerify.openai.api') . '/chat/completions', config('towerify.openai.api_key'), $messages, $model, $temperature, $tools);
    }

    private function callGemini(array $messages, string $model, float $temperature, array $tools = []): array
    {
        return $this->post(config('towerify.gemini.api') . '/chat/completions', config('towerify.gemini.api_key'), $messages, $model, $temperature, $tools);
    }

    private function post(string $url, string $bearer, array $messages, string $model, float $temperature, array $tools = []): array
    {
        try {

            $payload = [
                'model' => $model,
                'messages' => $messages,
                'temperature' => $temperature,
                'stream' => false,
            ];

            if (count($tools) > 0) {
                $payload['tools'] = $tools;
                $payload['tool_choice'] = 'auto';
            }

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$bearer}",
                'Accept' => 'application/json',
            ])
                ->timeout($this->timeoutInSeconds)
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