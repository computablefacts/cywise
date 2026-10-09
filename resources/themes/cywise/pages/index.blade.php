<?php

use App\Services\BlogContent;
use Illuminate\View\View;
use function Laravel\Folio\{name, render};

name('home');

render(function (View $view, BlogContent $content) {
    return $view->with('posts', $content->homePosts());
});
?>

<x-layouts.website-v2
    locale="fr"
    :language-url="route('website.en.home')"
    :seo="[
        'title' => 'Cywise — La cybersécurité pour tous',
        'description' => 'Cywise aide les entreprises à détecter leurs actifs exposés, leurs vulnérabilités et leurs identifiants compromis.',
    ]"
>
@include('theme::partials.website-v2.home', ['locale' => 'fr', 'posts' => $posts])
</x-layouts.website-v2>
