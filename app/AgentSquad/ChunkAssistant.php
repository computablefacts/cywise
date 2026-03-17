<?php

namespace App\AgentSquad;

use App\AgentSquad\Providers\HypotheticalQuestionsProvider;
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

    public function hypotheticalQuestions(): array
    {
        return HypotheticalQuestionsProvider::provide($this->lang->value, $this->chunk);
    }
}