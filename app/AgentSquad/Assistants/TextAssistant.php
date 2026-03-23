<?php

namespace App\AgentSquad\Assistants;

use App\AgentSquad\Providers\PromptsProvider;
use App\Enums\RoleEnum;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TextAssistant
{
    private string $model = 'Qwen/Qwen3-Next-80B-A3B-Instruct';
    private int $timeoutInSeconds = 60;
    private string|array|null $messages = null;

    public static function use(): TextAssistant
    {
        return new TextAssistant();
    }

    public function withTimeout(int $timeoutInSeconds): TextAssistant
    {
        $this->timeoutInSeconds = $timeoutInSeconds <= 0 ? 60 : $timeoutInSeconds;
        return $this;
    }

    public function withDeepInfraModel(string $model): TextAssistant
    {
        $this->model = $model;
        return $this;
    }

    public function withMessagesAndPrompt(array $messages, string $prompt, array $variables = []): TextAssistant
    {
        $messages[] = [
            'role' => RoleEnum::USER->value,
            'content' => PromptsProvider::use()
                ->withName($prompt)
                ->withVariables($variables)
                ->provide(),
        ];
        return $this->withMessages($messages);
    }

    public function withMessages(array $messages): TextAssistant
    {
        $this->messages = $messages;
        return $this;
    }

    public function withPrompt(string $prompt, array $variables = []): TextAssistant
    {
        return $this->withRawPrompt(
            PromptsProvider::use()
                ->withName($prompt)
                ->withVariables($variables)
                ->provide()
        );
    }

    public function withRawPrompt(string $prompt): TextAssistant
    {
        $this->messages = $prompt;
        return $this;
    }

    public function text(): string
    {
        if (is_string($this->messages)) {
            $messages = [[
                'role' => 'user',
                'content' => $this->messages
            ]];
        } else if (is_array($this->messages)) {
            $messages = $this->messages;
        } else {
            Log::error('TextAssistant messages must be either a string or an array');
            return '';
        }
        Log::debug(json_encode($messages));
        $response = $this->callDeepInfra($messages);
        Log::debug(json_encode($response));
        $answer = $response['choices'][0]['message']['content'] ?? '';
        $answer = Str::trim(preg_replace('/<think>.*?<\/think>/s', '', $answer));
        return Str::trim(Str::replace(['[OUTPUT]', '[/OUTPUT]'], '', $answer, false));
    }

    public function structured(): object
    {
        $matches = null;
        preg_match_all('/(?:```json\s*)?(.*)(?:\s*```)?/s', $this->text(), $matches);
        $raw = '{' . Str::after(Str::beforeLast(Str::trim($matches[1][0] ?? ''), '}'), '{') . '}'; //  deal with "}<｜end▁of▁sentence｜>"
        return (object)[
            'raw' => $raw,
            'parsed' => json_decode($raw, true),
        ];
    }

    private function callDeepInfra(array $messages): array
    {
        try {

            $url = config('towerify.deepinfra.api') . '/chat/completions';
            $bearer = config('towerify.deepinfra.api_key');
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$bearer}",
                'Accept' => 'application/json',
            ])
                ->timeout($this->timeoutInSeconds)
                ->post($url, [
                    'model' => $this->model,
                    'messages' => $messages,
                    'temperature' => 0.7,
                    'stream' => false,
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