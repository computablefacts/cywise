<?php

namespace App\AgentSquad\Providers;

use App\Helpers\LlmProvider;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LlmsProvider
{
    public static function provide(string|array $messages, ?string $model = null, int $timeoutInSeconds = 60): string
    {
        try {
            $start = microtime(true);
            $provider = new LlmProvider(LlmProvider::DEEP_INFRA, $timeoutInSeconds);
            $response = $provider->execute($messages, $model);
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
}