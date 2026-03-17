<?php

namespace App\AgentSquad;

use App\AgentSquad\Providers\LlmsProvider;
use App\AgentSquad\Providers\PromptsProvider;
use App\Enums\RoleEnum;

class TextAssistant
{
    private string $provider = 'deepinfra';
    private string $model = 'Qwen/Qwen3-Next-80B-A3B-Instruct';
    private int $timeoutInSeconds = 60;
    private string|array|null $messages = null;
    private string|array|null $response = null;

    public static function use(): TextAssistant
    {
        return new TextAssistant();
    }

    public function withMessagesAndPrompt(array $messages, string $prompt, array $variables = []): TextAssistant
    {
        $messages[] = [
            'role' => RoleEnum::USER->value,
            'content' => PromptsProvider::provide($prompt, $variables),
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
        return $this->withRawPrompt(PromptsProvider::provide($prompt, $variables));
    }

    public function withRawPrompt(string $prompt): TextAssistant
    {
        $this->messages = $prompt;
        return $this;
    }

    public function withTimeout(int $timeoutInSeconds): TextAssistant
    {
        $this->timeoutInSeconds = $timeoutInSeconds <= 0 ? 60 : $timeoutInSeconds;
        return $this;
    }

    public function withDeepInfra(string $model)
    {
        return $this->withProvider('deepinfra', $model);
    }

    public function withProvider(string $provider, string $model): TextAssistant
    {
        $this->provider = $provider;
        $this->model = $model;
        return $this;
    }

    public function text(): string
    {
        if (empty($this->response)) {
            $this->response = LlmsProvider::provide($this->messages, $this->model, $this->timeoutInSeconds);
        }
        return $this->response ?? '';
    }

    public function structured(): object
    {
        if (empty($this->response)) {
            $this->response = LlmsProvider::provideJson($this->messages, $this->model, $this->timeoutInSeconds);
        }
        return $this->response ?? (object)[];
    }
}