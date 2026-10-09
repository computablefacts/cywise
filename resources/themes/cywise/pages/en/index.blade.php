<?php

use App\Services\BlogContent;
use Illuminate\View\View;
use function Laravel\Folio\{name, render};

name('website.en.home');

render(function (View $view, BlogContent $content) {
    return $view->with('posts', $content->homePosts());
});
?>

<x-layouts.website-v2
    locale="en"
    :language-url="route('home')"
    :seo="[
        'title' => 'Cywise — Cybersecurity for Humans',
        'description' => 'Cywise helps companies identify exposed assets, vulnerabilities and compromised credentials.',
    ]"
>
@include('theme::partials.website-v2.home', ['locale' => 'en', 'posts' => $posts])
</x-layouts.website-v2>
