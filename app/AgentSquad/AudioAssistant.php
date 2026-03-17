<?php

namespace App\AgentSquad;

use App\AgentSquad\Providers\AudioToTextProvider;

class AudioAssistant
{
    private string $url;
    private string $lang = 'fr';
    private string|null $response = null;

    public static function use(): AudioAssistant
    {
        return new AudioAssistant();
    }

    public function withUrl(string $url): AudioAssistant
    {
        $this->url = $url;
        return $this;
    }

    public function withLang(string $lang): AudioAssistant
    {
        $this->lang = $lang;
        return $this;
    }

    public function text(): string
    {
        if (empty($this->response)) {
            $this->response = AudioToTextProvider::provide($this->url, $this->lang);
        }
        return $this->response ?? '';
    }
}