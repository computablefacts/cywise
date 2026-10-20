<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Laravel\Folio\Folio;

beforeEach(function () {

    // Routes and Folio paths are mounted at boot: reboot in legacy mode.
    // $_SERVER wins over the phpunit.xml value.
    $_SERVER['WEBSITE_UI'] = 'legacy';
    $this->refreshApplication();

    // Wave settings and theme components (e.g. <x-container>), used by the
    // legacy layout, come from the database.
    Cache::forever('wave_settings', []);
    Blade::anonymousComponentPath(resource_path('themes/cywise/components/elements'));
});

afterEach(function () {
    unset($_SERVER['WEBSITE_UI']);
});

it('mounts legacy pages before any other Folio path', function () {

    // In prod, devdojo/themes mounts the DB theme pages at boot.
    expect(Folio::paths()[0])->toBe(realpath(resource_path('themes/cywise/legacy/pages')));
});

it('resolves theme views to legacy ones', function () {
    expect(View::getFinder()->find('theme::page'))
        ->toBe(resource_path('themes/cywise/legacy/page.blade.php'))
        ->and(View::exists('theme::iframes.website'))->toBeTrue();
});

it('serves the former changelog', function () {
    $this->get('/changelog')
        ->assertOk()
        ->assertSee(route('iframes.changelog2'), false);
});

it('hides redesign-only sections', function (string $path) {
    $this->get($path)->assertNotFound();
})->with([
    '/en',
    '/en/pricing',
    '/solutions',
    '/solutions/pentest',
    '/for-whom',
    '/use-cases',
]);
