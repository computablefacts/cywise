<?php

namespace App\AgentSquad\Providers;

use Illuminate\Support\Facades\Log;

class HypotheticalQuestionsProvider
{
    public static function provide(string $language, string $text, string $prompt = 'default_hypothetical_questions'): array
    {
        try {
            $start = microtime(true);
            $prompt = PromptsProvider::provide($prompt, [
                'LANGUAGE' => $language,
                'TEXT' => $text,
            ]);
            $questions = LlmsProvider::provide($prompt, null, 3 * 60);
            $questions = array_values(array_filter(explode("\n", $questions), fn(string $question) => !empty($question)));
            $questions = array_map(fn(string $question) => [
                'question' => $question,
                'language' => $language,
                'embedding' => EmbeddingsProvider::provide($question)->embedding(),
            ], $questions);
            $stop = microtime(true);
            Log::debug("[HYPOTHETICAL_QUESTIONS_PROVIDER] " . count($questions) . " questions have been generated in " . ((int)ceil($stop - $start)) . " seconds");
            return $questions;
        } catch (\Exception $e) {
            Log::debug("[HYPOTHETICAL_QUESTIONS_PROVIDER] Questions generation failed");
            Log::error($e->getMessage());
            return [];
        }
    }
}