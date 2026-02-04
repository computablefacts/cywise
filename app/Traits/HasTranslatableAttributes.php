<?php

namespace App\Traits;

use App\AgentSquad\Providers\TranslationsProvider;
use App\Enums\LanguageEnum;

/**
 * This trait dynamically translates an attribute based on the language passed as a parameter.
 * By default, strings stored in a model are in english.
 */
trait HasTranslatableAttributes
{
    public function translated(string $key, LanguageEnum $lang = LanguageEnum::FRENCH): mixed
    {
        return TranslationsProvider::provide($this->{$key}, $lang);
    }
}
