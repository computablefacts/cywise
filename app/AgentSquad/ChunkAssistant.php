<?php

namespace App\AgentSquad;

use App\AgentSquad\Providers\EmbeddingsProvider;
use App\AgentSquad\Providers\HypotheticalQuestionsProvider;
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