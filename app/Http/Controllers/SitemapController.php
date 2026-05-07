<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Wave\Category;
use Wave\Changelog;
use Wave\Post;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $xml = Cache::remember('sitemap', now()->addDays(7), function () {

            // Initial URL map with default lastmod
            $urlToLastModified = collect();

            // Laravel Folio Pages
            $folio = resource_path('themes/cywise/pages');

            if (\Illuminate\Support\Facades\File::exists($folio)) {
                collect(\Illuminate\Support\Facades\File::allFiles($folio))
                    ->filter(fn(\SplFileInfo $file) => $file->getExtension() === 'php')
                    ->map(function (\SplFileInfo $file) use ($folio) {
                        $uri = $file->getRelativePathname();
                        if (Str::endsWith($uri, 'index.blade.php')) {
                            $uri = Str::beforeLast($uri, 'index.blade.php');
                        } else {
                            $uri = Str::beforeLast($uri, '.blade.php');
                        }
                        return [
                            'path' => Str::trim($uri, '/'),
                            'lastmod' => date('Y-m-d'),
                        ];
                    })
                    ->filter(fn(array $item) => !Str::contains($item['path'], ['[', ']'])) // Exclure les fichiers dynamiques Folio [...]
                    ->filter(fn(array $item) => !Str::startsWith($item['path'], ['layout', 'profile', 'recipe', 'settings', 'subscription', 'pricing'])) // Exclure les répertoires privés
                    ->filter(function (array $item) use ($folio) { // Exclure les fichiers utilisant le middleware('auth')
                        $uri = $item['path'];
                        $file = $folio . '/' . ($uri === '' ? 'index' : $uri) . '.blade.php';
                        if (!\Illuminate\Support\Facades\File::exists($file)) {
                            $file = $folio . '/' . $uri . '/index.blade.php';
                        }
                        if (\Illuminate\Support\Facades\File::exists($file)) {
                            $content = \Illuminate\Support\Facades\File::get($file);
                            if (Str::contains($content, "middleware('auth')") || Str::contains($content, 'middleware("auth")')) {
                                return false;
                            }
                        }
                        return true;
                    })
                    ->each(fn(array $item) => $urlToLastModified->put($item['path'], $item['lastmod']));
            }

            // Blog URLs
            $urlToLastModified->put('blog', date('Y-m-d'));

            try {
                Category::all()->each(fn(Category $c) => $urlToLastModified->put("blog/{$c->slug}", $c->updated_at?->format('Y-m-d') ?? date('Y-m-d')));
                Post::all()->each(fn(Post $p) => $urlToLastModified->put("blog/" . ($p->category->slug ?? 'all') . "/{$p->slug}", $p->updated_at?->format('Y-m-d') ?? date('Y-m-d')));
            } catch (\Exception $e) {
                Log::warning("Erreur blog: " . $e->getMessage());
            }

            // Changelog URLs
            $urlToLastModified->put('changelog', date('Y-m-d'));

            try {
                Changelog::all()->each(fn(Changelog $c) => $urlToLastModified->put("changelog/{$c->id}", $c->updated_at?->format('Y-m-d') ?? date('Y-m-d')));
            } catch (\Exception $e) {
                Log::warning("Erreur changelog: " . $e->getMessage());
            }

            // Build sitemap
            $baseUrl = Str::rtrim($baseUrl ?? config('app.url'), '/');
            $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>');

            $urlToLastModified->filter(fn($lastmod, $path) => !Str::contains($path, ['{', '}']))
                ->sortKeys()
                ->each(function (string $lastmod, string $path) use ($xml, $baseUrl) {
                    $path = Str::ltrim($path, '/');
                    $url = $xml->addChild('url');
                    $url->addChild('loc', htmlspecialchars($baseUrl . ($path === '' ? '' : "/$path")));
                    $url->addChild('lastmod', $lastmod);
                    $url->addChild('changefreq', 'weekly');
                    $url->addChild('priority', ($path === '' ? '1.0' : '0.8'));
                });

            $dom = new \DOMDocument('1.0');
            $dom->preserveWhiteSpace = false;
            $dom->formatOutput = true;
            $dom->loadXML($xml->asXML());

            return $dom->saveXML();
        });
        return response($xml, 200)->header('Content-Type', 'text/xml');
    }
}
