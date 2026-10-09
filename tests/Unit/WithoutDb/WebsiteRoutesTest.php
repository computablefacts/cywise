<?php

use App\Services\BlogContent;
use App\Services\PricingContent;
use Illuminate\Pagination\LengthAwarePaginator;
use Wave\Plan;

beforeEach(function () {
    $content = $this->mock(BlogContent::class);
    $content->shouldReceive('categories')->andReturn(collect());
    $content->shouldReceive('homePosts')->andReturn(collect());
    $content->shouldReceive('posts')->andReturn(new LengthAwarePaginator([], 0, 6));

    $pricing = $this->mock(PricingContent::class);
    $pricing->shouldReceive('plans')->andReturn(collect([
        new Plan([
            'name' => 'Essentiel',
            'description' => 'Pour les TPE.',
            'features' => 'CyberBuddy,Veille',
            'currency' => '€',
            'monthly_price' => '90',
            'monthly_price_id' => 'price_monthly',
            'yearly_price' => '900',
            'yearly_price_id' => 'price_yearly',
        ]),
    ]));
});

it('renders website SEO metadata', function () {
    $this->get('/solutions')
        ->assertOk()
        ->assertSee('rel="canonical"', false)
        ->assertSee('name="robots"', false)
        ->assertSee('property="og:title"', false)
        ->assertSee('property="og:description"', false)
        ->assertSee('build/assets/ui-', false)
        ->assertDontSee('website-v2/styles.css', false);
});

it('keeps blog quotes readable', function () {
    $prose = file_get_contents(resource_path('themes/cywise/components/site/prose.blade.php'));

    expect($prose)
        ->toContain('ui:[&_blockquote]:border-brand-500')
        ->toContain('ui:[&_blockquote]:text-ink');
});

it('renders localized website copy', function () {
    $this->get('/en')
        ->assertOk()
        ->assertSee('Cywise helps companies identify exposed assets', false)
        ->assertDontSee('Redesign conceptuel', false);

    $this->get('/use-cases')
        ->assertOk()
        ->assertSee('Créer une PSSI', false)
        ->assertDontSee('Create a PSSI', false);
});

it('renders the login page', function () {
    $this->get('/auth/login')->assertOk();
});

it('connects pricing plans to billing', function () {
    $this->get('/pricing')
        ->assertOk()
        ->assertSee('Essentiel', false)
        ->assertSee('90', false)
        ->assertSee('900', false)
        ->assertSee(route('settings.subscription'), false)
        ->assertSee('3 000 €+', false);

    $this->get('/en/pricing')
        ->assertOk()
        ->assertSee('Essentiel', false)
        ->assertSee('90', false)
        ->assertSee('900', false)
        ->assertSee(route('settings.subscription'), false)
        ->assertSee('€3,000+', false);
});

it('brands the header and footer with the wordmark and severity strip', function () {
    $header = file_get_contents(resource_path('themes/cywise/partials/website-v2/header.blade.php'));
    $footer = file_get_contents(resource_path('themes/cywise/partials/website-v2/footer.blade.php'));

    expect($header)
        ->toContain('>CYWISE</a>')
        ->toContain('<x-site.strip/>');
    expect($footer)
        ->toContain('>CYWISE</a>')
        ->toContain('<x-site.strip reverse/>');
});

it('keeps blog cards visual without a cover image', function () {
    $posts = file_get_contents(resource_path('themes/cywise/partials/website-v2/posts-loop.blade.php'));

    expect($posts)
        ->toContain('@if ($post->image())')
        ->toContain('<x-site.strip/>');
});

it('gives every solution tone a card edge colour', function () {
    $card = file_get_contents(resource_path('themes/cywise/components/site/edge-card.blade.php'));

    foreach (['critical', 'medium', 'low', 'info', 'ink', 'line'] as $tone) {
        expect($card)->toContain("'{$tone}' => 'ui:bg-");
    }
});

it('serves public changelogs with the website layout', function () {
    $pages = [
        resource_path('themes/cywise/pages/changelog/index.blade.php'),
        resource_path('themes/cywise/pages/changelog/[.Wave.Changelog].blade.php'),
    ];
    $routes = file_get_contents(base_path('routes/web.php'));

    expect($routes)->not->toContain("Route::get('/changelog'");

    foreach ($pages as $page) {
        $template = file_get_contents($page);

        expect($template)
            ->toContain("'layouts.website-v2'")
            ->toContain("'layouts.app'")
            ->not->toContain("'layouts.marketing'");
    }
});

it('renders public website pages', function (string $path) {
    $this->get($path)->assertOk();
})->with([
    '/',
    '/blog',
    '/en/solutions',
    '/en/blog',
    '/for-whom',
    '/en/for-whom',
    '/use-cases',
    '/en/use-cases',
    '/pricing',
    '/en/pricing',
]);
