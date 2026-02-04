<?php

namespace App\Helpers;

use App\AgentSquad\Providers\LlmsProvider;
use App\AgentSquad\Providers\PromptsProvider;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ApiUtils
{
    private const string MODEL_TRANSLATE = 'meta-llama/Meta-Llama-3.1-8B-Instruct-Turbo';

    public function translate(string $text, string $lang = 'fr'): array
    {
        $prompt = PromptsProvider::provide('default_translate', [
            'TEXT' => $text,
            'LANG' => $lang,
        ]);

        $answer = LlmsProvider::provide($prompt, self::MODEL_TRANSLATE);

        return $answer === '' ?
        [
            'error' => true,
            'error_details' => "Unable to translate $text in $lang language.",
            'response' => $text,
        ] :
        [
            'error' => false,
            'error_details' => '',
            'response' => $answer,
        ];
    }

    public function whisper(string $url, string $lang = 'fr')
    {
        return $this->post('/api/whisper', [
            'url' => $url,
            'lang' => $lang,
        ]);
    }

    public function file_input(string $client, string $url, ?string $filename = null): array
    {
        return $this->post('/api/file-input', [
            'url' => $url,
            'client' => $client,
            'filename' => $filename,
        ]);
    }

    private function post($endpoint, $json): array
    {
        $url = Config::get('towerify.cyberbuddy.api') . $endpoint;

        $response = Http::timeout(180)
            ->withBasicAuth(
                config('towerify.cyberbuddy.api_username'),
                config('towerify.cyberbuddy.api_password')
            )->withHeaders([
                'Accept' => 'application/json',
            ])->post($url, $json);

        if ($response->successful()) {
            $json = $response->json();
            // Log::debug($json);
            return $json ?: [];
        }
        Log::error($response->body());
        return [];
    }
}
