<?php

use App\Services\BlogContent;
use Illuminate\View\View;
use function Laravel\Folio\{name, render};

name('blog');

render(function (View $view, BlogContent $content) {
    return $view->with([
        'posts' => $content->posts(),
        'categories' => $content->categories(),
    ]);
});
?>

<x-layouts.website-v2
    locale="fr"
    :language-url="route('blog.en')"
    :seo="[
        'title' => 'Blog — Cywise',
        'description' => 'Guides et conseils pratiques de Cywise pour mieux gérer votre cybersécurité.',
    ]"
>
    <main>
        <section class="page-hero page-hero-pink">
            <div class="container-fluid shell">
                <span class="mono">BLOG / RESSOURCES</span>
                <h1>DEPUIS LE LAB CYWISE.</h1>
                <p>Des contenus cybersécurité pratiques pour les équipes qui veulent des réponses claires.</p>
            </div>
        </section>

        <section class="section-pad">
            <div class="container-fluid shell">
                @include('theme::partials.website-v2.categories', [
                    'categories' => $categories,
                    'locale' => 'fr',
                ])

                <div class="row g-4 mt-3">
                    @include('theme::partials.website-v2.posts-loop', [
                        'posts' => $posts,
                        'locale' => 'fr',
                    ])
                </div>

                {{ $posts->links('theme::partials.website-v2.pagination') }}
            </div>
        </section>
    </main>
</x-layouts.website-v2>
