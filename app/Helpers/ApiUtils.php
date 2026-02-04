<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ApiUtils
{
    public function translate(string $text, string $lang = 'fr', string $prompt = ''): array
    {
        return $this->post('/translate', [
            'model_name' => 'default',
            'prompt' => $prompt,
            'text' => $text,
            'lang' => $lang,
        ]);
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
