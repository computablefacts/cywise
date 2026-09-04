<?php

use App\Services\BlogContent;
use Illuminate\View\View;
use Wave\Category;
use function Laravel\Folio\{name, render};

name('blog.en.category');

render(function (View $view, BlogContent $content, Category $category) {
    return $view->with([
        'posts' => $content->posts($category),
        'categories' => $content->categories(),
    ]);
});
?>

<x-layouts.website-v2
    locale="en"
    :language-url="route('blog.category', ['category' => $category])"
    :seo="[
        'title' => $category->name . ' — Cywise Blog',
        'description' => 'Cywise articles in the ' . $category->name . ' category.',
    ]"
>
    <main>
        <section class="page-hero page-hero-pink">
            <div class="container-fluid shell">
                <span class="mono">BLOG / {{ $category->name }}</span>
                <h1>{{ mb_strtoupper($category->name) }}</h1>
                <p>The latest content in this category.</p>
            </div>
        </section>

        <section class="section-pad">
            <div class="container-fluid shell">
                @include('theme::partials.website-v2.categories', [
                    'categories' => $categories,
                    'category' => $category,
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
