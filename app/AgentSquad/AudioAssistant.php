<?php

namespace App\AgentSquad;

use App\AgentSquad\Providers\AudioToTextProvider;
use App\Enums\LanguageEnum;

class AudioAssistant
{
    private string $url;
    private LanguageEnum $lang = LanguageEnum::FRENCH;

    public static function use(): AudioAssistant
    {
        return new AudioAssistant();
    }

    public function withUrl(string $url): AudioAssistant
    {
        $this->url = $url;
        return $this;
    }

    public function withLang(LanguageEnum $lang): AudioAssistant
    {
        $this->lang = $lang;
        return $this;
    }

    public function text(): string
    {
        return AudioToTextProvider::provide($this->url, $this->lang->value);
    }
}