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
        <section class="ui:mx-auto ui:max-w-[1240px] ui:px-4 ui:pb-12 ui:pt-16 ui:sm:px-6">
            <x-site.display as="h1" size="xl" class="ui:text-[clamp(30px,4.2vw,54px)]!">{{ $category->name }}</x-site.display>
            <p class="ui:m-0 ui:mt-6 ui:max-w-[560px] ui:text-xl ui:leading-normal ui:text-slate-700">The latest content in this category.</p>
        </section>

        <section class="ui:mx-auto ui:max-w-[1240px] ui:px-4 ui:pb-24 ui:sm:px-6">
            @include('theme::partials.website-v2.categories', [
                'categories' => $categories,
                'category' => $category,
                'locale' => 'en',
            ])

            @if ($posts->isEmpty())
                <p class="ui:m-0 ui:mt-8 ui:rounded-2xl ui:border ui:border-dashed ui:border-line ui:px-6 ui:py-16 ui:text-center ui:text-lg ui:text-slate-600">No articles in this category yet.</p>
            @else
                <div class="ui:mt-8 ui:grid ui:grid-cols-1 ui:gap-3 ui:md:grid-cols-2 ui:xl:grid-cols-3">
                    @include('theme::partials.website-v2.posts-loop', [
                        'posts' => $posts,
                        'locale' => 'en',
                    ])
                </div>
            @endif

            {{ $posts->links('theme::partials.website-v2.pagination') }}
        </section>
    </main>
</x-layouts.website-v2>
