<?php

namespace App\AgentSquad\Providers;

use App\Enums\LanguageEnum;
use App\Helpers\ApiUtilsFacade as ApiUtils;

/** Translates a string from english to another language. */
class TranslationsProvider
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
            $result = ApiUtils::translate($value, $lang->value);
            return $result['error'] !== false ? $value : $result['response'];
        });
    }
}