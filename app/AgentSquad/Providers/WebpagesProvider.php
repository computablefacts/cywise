<?php

namespace App\AgentSquad\Providers;

use App\Enums\LanguageEnum;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WebpagesProvider extends AbstractProvider
{
    private string $url;
    private LanguageEnum $proxyCountry = LanguageEnum::FRENCH;

    public static function isHyperlink(string $text): bool
    {
        return Str::startsWith(Str::lower($text), ["https://", "http://"]);
    }

    public static function use(): WebpagesProvider
    {
        return new WebpagesProvider();
    }

    public function withUrl(string $url): WebpagesProvider
    {
        $this->url = $url;
        return $this;
    }

    public function withProxyCountry(LanguageEnum $country): WebpagesProvider
    {
        $this->proxyCountry = $country;
        return $this;
    }

    protected function provide2(): string
    {
        try {
            return self::callScrapflyOrScraperApi($this->url, $this->proxyCountry->value);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
        return '';
    }

    private static function callScrapflyOrScraperApi(string $text, string $country): string
    {
        if (self::isHyperlink($text)) {
            if (config('towerify.scrapfly.api_key')) {
                $news = Http::get('https://api.scrapfly.io/scrape?render_js=true&asp=true&cache=true&cache_ttl=86400&key=' . config('towerify.scrapfly.api_key') . "&country={$country}&url={$text}");
                return json_decode($news, true)['result']['content'];
            }
            if (config('towerify.scraperapi.api_key')) {
                return Http::get('http://api.scraperapi.com?api_key=' . config('towerify.scraperapi.api_key') . '&url=' . $text);
            }
            Log::error('Missing scraper API key!');
            return '';
        }
        return $text;
    }
}