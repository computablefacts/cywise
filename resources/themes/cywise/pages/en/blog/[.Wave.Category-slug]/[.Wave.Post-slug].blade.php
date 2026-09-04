<?php

use App\Services\BlogContent;
use Illuminate\View\View;
use Wave\Category;
use Wave\Post;
use function Laravel\Folio\{name, render};

name('blog.en.post');

render(function (View $view, BlogContent $content, Category $category, Post $post) {
    $content->assertPost($post, $category);

    return $view->with('readingMinutes', $content->readingMinutes($post));
});
?>

<x-layouts.website-v2
    locale="en"
    :language-url="route('blog.post', ['category' => $category, 'post' => $post])"
    :seo="[
        'title' => ($post->seo_title ?: $post->title) . ' — Cywise',
        'description' => $post->meta_description ?: ($post->excerpt ?? ''),
    ]"
>
    <main>
        <section class="blogpost-hero">
            <div class="container-fluid shell">
                <a class="mono blog-back" href="{{ route('blog.en') }}">← BACK TO THE BLOG</a>
                <div class="mono blogpost-kicker">{{ $category->name }} / {{ $readingMinutes }} MIN</div>
                <h1>{{ $post->title }}</h1>

                @if ($post->excerpt)
                    <p class="blogpost-lead">{{ $post->excerpt }}</p>
                @endif

                <div class="blogpost-meta mono">
                    <span>{{ $post->user->name }}</span>
                    <span>{{ $post->created_at->format('Y.m.d') }}</span>
                    <span>{{ $readingMinutes }} MIN</span>
                </div>
            </div>
        </section>

        <section class="blogpost-body section-pad">
            <div class="container-fluid shell">
                @if ($post->image())
                    <img class="blogpost-image" src="{{ $post->image() }}" alt="{{ $post->title }}">
                @endif

                <article class="blogpost-article">
                    {!! $post->body !!}
                </article>
            </div>
        </section>

        <section class="blogpost-cta">
            <div class="container-fluid shell text-center">
                <span class="mono">CYWISE / NEXT STEP</span>
                <h2>SEE YOUR EXPOSURE BEFORE ATTACKERS DO.</h2>
                <a class="btn btn-dark-brutal btn-xl mt-4" href="{{ route('register') }}">START FOR FREE →</a>
            </div>
        </section>
    </main>
</x-layouts.website-v2>
