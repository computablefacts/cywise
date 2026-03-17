<?php

namespace App\Traits;

use App\AgentSquad\ChunkAssistant;
use App\Enums\LanguageEnum;

/**
 * This trait dynamically translates an attribute based on the language passed as a parameter.
 * By default, strings stored in a model are in english.
 */
trait HasTranslatableAttributes
{
    public function translated(string $key, LanguageEnum $lang = LanguageEnum::FRENCH): mixed
    {
        return ChunkAssistant::use()
            ->withLang(LanguageEnum::ENGLISH)
            ->withChunk($this->{$key})
            ->translate($lang);
    }
}
