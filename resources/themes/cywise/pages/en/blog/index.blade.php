<?php

use App\Services\BlogContent;
use Illuminate\View\View;
use function Laravel\Folio\{name, render};

name('blog.en');

render(function (View $view, BlogContent $content) {
    return $view->with([
        'posts' => $content->posts(),
        'categories' => $content->categories(),
    ]);
});
?>

<x-layouts.website-v2
    locale="en"
    :language-url="route('blog')"
    :seo="[
        'title' => 'Blog — Cywise',
        'description' => 'Practical cybersecurity guides and advice from Cywise.',
    ]"
>
    <main>
        <section class="page-hero page-hero-pink">
            <div class="container-fluid shell">
                <span class="mono">BLOG / RESOURCES</span>
                <h1>FROM THE CYWISE LAB.</h1>
                <p>Practical cybersecurity content for teams that need clear answers.</p>
            </div>
        </section>

        <section class="section-pad">
            <div class="container-fluid shell">
                @include('theme::partials.website-v2.categories', [
                    'categories' => $categories,
                    'locale' => 'en',
                ])

                <div class="row g-4 mt-3">
                    @include('theme::partials.website-v2.posts-loop', [
                        'posts' => $posts,
                        'locale' => 'en',
                    ])
                </div>

                {{ $posts->links('theme::partials.website-v2.pagination') }}
            </div>
        </section>
    </main>
</x-layouts.website-v2>
