<?php

use App\Services\BlogContent;
use Illuminate\Pagination\LengthAwarePaginator;

beforeEach(function () {
    $content = $this->mock(BlogContent::class);
    $content->shouldReceive('categories')->andReturn(collect());
    $content->shouldReceive('homePosts')->andReturn(collect());
    $content->shouldReceive('posts')->andReturn(new LengthAwarePaginator([], 0, 6));
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

it('renders static pricing', function () {
    $this->get('/pricing')
        ->assertOk()
        ->assertSee('SUR MESURE', false)
        ->assertSee('3 000 €+', false);

    $this->get('/en/pricing')
        ->assertOk()
        ->assertSee('CUSTOM', false)
        ->assertSee('€3,000+', false);
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
