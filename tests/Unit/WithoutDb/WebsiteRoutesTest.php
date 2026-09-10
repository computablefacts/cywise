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
        ->assertSee('styles.css?v=', false)
        ->assertSee('app.js?v=', false);
});

it('keeps blog quotes readable', function () {
    $styles = file_get_contents(public_path('cywise/website-v2/styles.css'));

    expect($styles)
        ->toContain('.blogpost-article blockquote p{')
        ->toContain('color:inherit');
});

it('renders localized website copy', function () {
    $this->get('/en')
        ->assertOk()
        ->assertSee('Cywise helps companies identify exposed assets', false)
        ->assertDontSee('Redesign conceptuel', false);

    $this->get('/use-cases')
        ->assertOk()
        ->assertSee('CRÉER UNE PSSI', false)
        ->assertDontSee('CREATE A PSSI', false);
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

it('uses the supplied logo in the header and footer', function () {
    $header = file_get_contents(resource_path('themes/cywise/partials/website-v2/header.blade.php'));
    $footer = file_get_contents(resource_path('themes/cywise/partials/website-v2/footer.blade.php'));

    expect($header)
        ->toContain('cywise-logo-riso.png')
        ->toContain('filemtime(public_path($logoPath))')
        ->toContain('cw-logo');
    expect($footer)
        ->toContain('cywise-logo-riso.png')
        ->toContain('filemtime(public_path($logoPath))')
        ->toContain('footer-logo');
});

it('keeps blog card visuals and spacing stable', function () {
    $posts = file_get_contents(resource_path('themes/cywise/partials/website-v2/posts-loop.blade.php'));
    $styles = file_get_contents(public_path('cywise/website-v2/styles.css'));

    expect($posts)
        ->toContain('$post->getKey() % count($visuals)')
        ->toContain('class="article-card-content"')
        ->not->toContain('$loop->index % count($visuals)');
    expect($styles)
        ->toContain('.article-card-content{')
        ->toContain('padding:22px 24px 24px');
});

it('keeps the yellow feature-card shadow visible', function () {
    $styles = file_get_contents(public_path('cywise/website-v2/styles.css'));

    expect($styles)
        ->toContain('.row > :nth-child(5n+1) > .solution-card{')
        ->toContain('box-shadow:6px 6px 0 #16234A');
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
