<?php

namespace App\AgentSquad;

use App\AgentSquad\Vectors\Vector;
use App\Enums\LanguageEnum;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChunkAssistant
{
    private string $chunk;
    private int $timeoutInSeconds = 60;
    private LanguageEnum $lang = LanguageEnum::FRENCH;

    public static function use(): ChunkAssistant
    {
        return new ChunkAssistant();
    }

    public function withTimeout(int $timeoutInSeconds): ChunkAssistant
    {
        $this->timeoutInSeconds = $timeoutInSeconds <= 0 ? 60 : $timeoutInSeconds;
        return $this;
    }

    public function withChunk(string $chunk): ChunkAssistant
    {
        $this->chunk = $chunk;
        return $this;
    }

    public function withLang(LanguageEnum $lang): ChunkAssistant
    {
        $this->lang = $lang;
        return $this;
    }

    // Translates a string from english to another language
    public function translate(LanguageEnum $lang = LanguageEnum::FRENCH): string
    {
        // Here, the assumption is that $this->chunk is in english, e.g. $this->lang = LanguageEnum::ENGLISH, and $lang is the target language
        if (empty($this->chunk)) {
            return '';
        }
        if ($lang === $this->lang) {
            return $this->chunk;
        }

        $key = 'translation:' . $this->lang->value . ':' . $lang->value . ':' . md5($this->chunk);

        return \Cache::remember($key, now()->addDays(120), function () use ($lang) {

            $answer = TextAssistant::use()
                ->withTimeout($this->timeoutInSeconds)
                ->withPrompt('default_translate', [
                    'TEXT' => $this->chunk,
                    'LANG' => $lang->value,
                ])
                ->withDeepInfra('meta-llama/Meta-Llama-3.1-8B-Instruct-Turbo')
                ->text();

            if ($answer === '') {
                Log::warning("Unable to translate {$this->chunk} from {$this->lang->value} to {$lang->value} language.");
                return $this->chunk;
            }
            return $answer;
        });
    }

    public function hypotheticalQuestions(): array
    {
        $key = 'hypothetical_questions_provider_' . md5($this->lang->value . $this->chunk);

        return \Cache::remember($key, now()->addDays(7), function () {

            $questions = TextAssistant::use()
                ->withTimeout($this->timeoutInSeconds)
                ->withPrompt('default_hypothetical_questions', [
                    'LANGUAGE' => $this->lang->value,
                    'TEXT' => $this->chunk,
                ])
                ->text();

            $questions = array_values(array_filter(explode("\n", $questions), fn(string $question) => !empty($question)));

            return array_map(fn(string $question) => [
                'question' => $question,
                'language' => $this->lang->value,
                'embedding' => ChunkAssistant::use()
                    ->withTimeout($this->timeoutInSeconds)
                    ->withChunk($question)
                    ->embedding(),
            ], $questions);
        });
    }

    public function embedding(array $metadata = []): array
    {
        return $this->vector($metadata)?->embedding() ?? [];
    }

    public function vector(array $metadata = []): ?Vector
    {
        $key = 'embeddings_provider_' . md5($this->chunk);

        return \Cache::remember($key, now()->addDays(7), function () use ($metadata) {
            $embedding = $this->callDeepInfra()['data'][0]['embedding'] ?? [];
            return empty($embedding) ? null : new Vector($this->chunk, $embedding, $metadata);
        });
    }

    private function callDeepInfra(): array
    {
        try {

            $url = config('towerify.deepinfra.api') . '/embeddings';
            $bearer = config('towerify.deepinfra.api_key');
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$bearer}",
                'Accept' => 'application/json',
            ])
                ->timeout($this->timeoutInSeconds)
                ->post($url, [
                    'model' => 'BAAI/bge-m3-multi',
                    'input' => $this->chunk,
                    'encoding_format' => 'float',
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