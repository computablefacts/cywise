<?php

namespace App\AgentSquad\Providers;

use App\AgentSquad\TextAssistant;
use App\Enums\LanguageEnum;
use Illuminate\Support\Facades\Log;

/** Translates a string from english to another language. */

/** @deprecated */
class TranslationsProvider extends AbstractProvider
{
    public static function provide(string $value, LanguageEnum $lang = LanguageEnum::FRENCH): string
    {
        if (empty($value)) {
            return '';
        }
        if ($lang === LanguageEnum::ENGLISH) {
            return $value;
        }

        $key = 'translation:en:' . $lang->value . ':' . md5($value);

        return \Cache::remember($key, now()->addDays(120), function () use ($value, $lang) {

            $before = microtime(true);

            try {

                $answer = TextAssistant::use()
                    ->withPrompt('default_translate', [
                        'TEXT' => $value,
                        'LANG' => $lang->value,
                    ])
                    ->withDeepInfra('meta-llama/Meta-Llama-3.1-8B-Instruct-Turbo')
                    ->text();

                if ($answer === '') {
                    Log::warning("Unable to translate {$value} in {$lang->value} language.");
                    $answer = $value;
                }

            } catch (\Exception $e) {
                Log::error($e->getMessage());
                $answer = null;
            }

            $after = microtime(true);

            if (isset($answer)) {
                self::traceSuccess('translations', $before, $after);
                return $answer;
            }

            self::traceError('translations', $before, $after);
            return '';
        });
    }
}