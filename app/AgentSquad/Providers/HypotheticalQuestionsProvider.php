<?php

namespace App\AgentSquad\Providers;

use App\AgentSquad\Assistant;
use Illuminate\Support\Facades\Log;

class HypotheticalQuestionsProvider extends AbstractProvider
{
    public static function provide(string $language, string $text, string $prompt = 'default_hypothetical_questions'): array
    {
        return \Cache::remember('hypothetical_questions_provider_' . md5($language . $text . $prompt), now()->addDays(7), function () use ($language, $text, $prompt) {

            $before = microtime(true);

            try {
                $questions = Assistant::use()
                    ->withTimeout(3 * 60)
                    ->withPrompt($prompt, [
                        'LANGUAGE' => $language,
                        'TEXT' => $text,
                    ])
                    ->text();
                $questions = array_values(array_filter(explode("\n", $questions), fn(string $question) => !empty($question)));
                $questions = array_map(fn(string $question) => [
                    'question' => $question,
                    'language' => $language,
                    'embedding' => EmbeddingsProvider::provide($question)->embedding(),
                ], $questions);
            } catch (\Exception $e) {
                Log::error($e->getMessage());
                $questions = null;
            }

            $after = microtime(true);

            if (isset($questions)) {
                self::traceSuccess('hypothetical-questions', $before, $after);
                return $questions;
            }

            self::traceError('hypothetical-questions', $before, $after);
            return [];
        });
    }
}