<?php

namespace App\AgentSquad;

use App\AgentSquad\Providers\EmbeddingsProvider;
use App\AgentSquad\Providers\HypotheticalQuestionsProvider;
use App\AgentSquad\Providers\TranslationsProvider;
use App\AgentSquad\Vectors\Vector;
use App\Enums\LanguageEnum;

class ChunkAssistant
{
    private string $chunk;
    private LanguageEnum $lang = LanguageEnum::FRENCH;

    public static function use(): ChunkAssistant
    {
        return new ChunkAssistant();
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
        return TranslationsProvider::provide($this->chunk, $lang);
    }

    public function hypotheticalQuestions(): array
    {
        return HypotheticalQuestionsProvider::provide($this->lang->value, $this->chunk);
    }

    public function embedding(array $metadata = []): array
    {
        return $this->vector($metadata)?->embedding() ?? [];
    }

    public function vector(array $metadata = []): ?Vector
    {
        return EmbeddingsProvider::provide($this->chunk, $metadata);
    }
}