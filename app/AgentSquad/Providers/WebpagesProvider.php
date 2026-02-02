<?php

namespace App\AgentSquad\Providers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WebpagesProvider
{
    public static function provide(string $url, string $country = 'fr'): string
    {
        try {
            $start = microtime(true);
            $answer = self::callScrapfly($url, $country);
            $stop = microtime(true);
            Log::debug("[WEBPAGES_PROVIDER] Crawler api call took " . ((int)ceil($stop - $start)) . " seconds");
            return $answer;
        } catch (\Exception $e) {
            Log::debug("[WEBPAGES_PROVIDER] Crawler api call failed");
            Log::error($e->getMessage());
            return '';
        }
    }

    public static function isHyperlink(string $text): bool
    {
        return Str::startsWith(Str::lower($text), ["https://", "http://"]);
    }

    private static function callScrapfly(string $text, string $country = 'fr'): string
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