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
        <article class="ui:mx-auto ui:max-w-[880px] ui:px-4 ui:pb-24 ui:pt-12 ui:sm:px-6">
            <header>
                <div class="ui:flex ui:flex-wrap ui:items-center ui:justify-between ui:gap-4 ui:font-mono ui:text-[13px] ui:uppercase">
                    <a class="ui:text-slate-600! ui:no-underline! ui:hover:text-ink!" href="{{ route('blog.en') }}">← Back to the blog</a>
                    <a class="ui:text-brand-700! ui:no-underline! ui:hover:text-ink!" href="{{ route('blog.en.category', ['category' => $category]) }}">{{ $category->name }}</a>
                </div>

                <x-site.display as="h1" class="ui:mt-8 ui:text-[clamp(28px,4.4vw,60px)]!">{{ $post->title }}</x-site.display>

                @if ($post->excerpt)
                    <p class="ui:m-0 ui:mt-6 ui:max-w-[720px] ui:text-[22px] ui:leading-normal ui:text-slate-700">{{ $post->excerpt }}</p>
                @endif

                {{-- Meta row on a thin ink rule: author · date · reading time --}}
                <p class="ui:m-0 ui:mt-10 ui:flex ui:flex-wrap ui:gap-x-3 ui:gap-y-1 ui:border-0 ui:border-b ui:border-solid ui:border-ink ui:pb-4 ui:font-mono ui:text-[13px] ui:uppercase ui:text-slate-600">
                    <span>{{ $post->user->name }}</span>
                    <span aria-hidden="true">·</span>
                    <span>{{ $post->created_at->format('Y.m.d') }}</span>
                    <span aria-hidden="true">·</span>
                    <span>{{ $readingMinutes }} min</span>
                </p>
            </header>

            @if ($post->image())
                <img class="ui:mt-10 ui:h-auto ui:w-full ui:rounded-3xl" src="{{ $post->image() }}" alt="{{ $post->title }}">
            @endif

            {{-- Body: HTML from the DB, styled through descendant selectors --}}
            <x-site.prose class="ui:mt-12">
                {!! $post->body !!}
            </x-site.prose>
        </article>

        <x-site.cta tone="dark" :href="route('register')" label="Start for free →">
            <x-slot:title>See your exposure before attackers do.</x-slot:title>
        </x-site.cta>
    </main>
</x-layouts.website-v2>
