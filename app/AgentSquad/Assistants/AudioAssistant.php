<?php

namespace App\AgentSquad\Assistants;

use App\Enums\LanguageEnum;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AudioAssistant
{
    private string $url;
    private int $timeoutInSeconds = 60;
    private LanguageEnum $lang = LanguageEnum::FRENCH;

    public static function use(): AudioAssistant
    {
        return new AudioAssistant();
    }

    public function withTimeout(int $timeoutInSeconds): AudioAssistant
    {
        $this->timeoutInSeconds = $timeoutInSeconds <= 0 ? 60 : $timeoutInSeconds;
        return $this;
    }

    public function withUrl(string $url): AudioAssistant
    {
        $this->url = $url;
        return $this;
    }

    public function withLang(LanguageEnum $lang): AudioAssistant
    {
        $this->lang = $lang;
        return $this;
    }

    public function text(): string
    {
        $audio = $this->download();
        if (!empty($audio)) {
            try {
                $response = $this->callDeepInfra(base64_encode($audio));
                return $response['text'] ?? '';
            } catch (\Exception $e) {
                Log::error($e->getMessage());
            }
        }
        return '';
    }

    private function download(): ?string
    {
        try {
            $response = Http::get($this->url);
            if ($response->failed()) {
                Log::error("Failed to download file from URL: {$this->url}", [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                return null;
            }
            return $response->body();
        } catch (\Exception $e) {
            Log::error("Error encoding audio to base64: {$e->getMessage()}", [
                'url' => $this->url,
                'exception' => $e
            ]);
            return null;
        }
    }

    private function callDeepInfra(string $audio): array
    {
        try {

            $url = config('towerify.deepinfra.api') . '/../inference/openai/whisper-large-v3-turbo';
            $bearer = config('towerify.deepinfra.api_key');
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$bearer}",
                'Accept' => 'application/json',
            ])
                ->timeout($this->timeoutInSeconds)
                ->post($url, [
                    'audio' => $audio,
                    'lang' => $this->lang->value,
                    'text' => '',
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error($response->body());

        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
        return [];
    }
}