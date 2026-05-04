<?php

declare(strict_types=1);

namespace Tests\Feature\AgentSquad\Providers;

use App\AgentSquad\Providers\WebpagesProvider;
use App\Enums\LanguageEnum;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCaseWithDb;

final class WebpagesProviderTest extends TestCaseWithDb
{
    public function test_is_hyperlink_detects_urls(): void
    {
        $this->assertTrue(WebpagesProvider::isHyperlink('https://example.com'));
        $this->assertTrue(WebpagesProvider::isHyperlink('http://example.com'));
        $this->assertFalse(WebpagesProvider::isHyperlink('Not a URL'));
        $this->assertFalse(WebpagesProvider::isHyperlink('ftp://example.com'));
    }

    public function test_provide_calls_scrapfly_api_when_only_scrapfly_api_key_exists(): void
    {
        Config::set('towerify.scrapfly.api_key', 'test_key');

        Http::fake([
            'api.scrapfly.io/*' => Http::response([
                'result' => [
                    'content' => 'Scrapfly content'
                ]
            ], 200),
        ]);

        $result = WebpagesProvider::use()
            ->withUrl('https://example.com')
            ->withProxyCountry(LanguageEnum::FRENCH)
            ->provide();

        $this->assertEquals('Scrapfly content', $result);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'api.scrapfly.io') &&
                str_contains($request->url(), 'key=test_key') &&
                str_contains($request->url(), 'country=fr');
        });
    }

    public function test_provide_calls_scraperapi_when_only_scraperapi_key_exists(): void
    {
        Config::set('towerify.scrapfly.api_key', null);
        Config::set('towerify.scraperapi.api_key', 'test_scraper_key');

        Http::fake([
            'api.scraperapi.com*' => Http::response('ScraperAPI content', 200),
        ]);

        $result = WebpagesProvider::use()
            ->withUrl('https://example.com')
            ->provide();

        $this->assertEquals('ScraperAPI content', $result);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'api.scraperapi.com') &&
                str_contains($request->url(), 'api_key=test_scraper_key');
        });
    }

    public function test_provide_returns_original_text_if_not_hyperlink(): void
    {
        $result = WebpagesProvider::use()
            ->withUrl('Just some text')
            ->provide();

        $this->assertEquals('Just some text', $result);
        
        Http::assertNothingSent();
    }
}
