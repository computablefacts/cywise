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
        <section class="ui:mx-auto ui:max-w-[1240px] ui:px-4 ui:pb-12 ui:pt-16 ui:sm:px-6">
            <x-site.display as="h1" size="xl" class="ui:text-[clamp(30px,4.2vw,54px)]!">From the Cywise lab.</x-site.display>
            <p class="ui:m-0 ui:mt-6 ui:max-w-[560px] ui:text-xl ui:leading-normal ui:text-slate-700">Practical cybersecurity content for teams that need clear answers.</p>
        </section>

        <section class="ui:mx-auto ui:max-w-[1240px] ui:px-4 ui:pb-24 ui:sm:px-6">
            @include('theme::partials.website-v2.categories', [
                'categories' => $categories,
                'locale' => 'en',
            ])

            @if ($posts->isEmpty())
                <p class="ui:m-0 ui:mt-8 ui:rounded-2xl ui:border ui:border-dashed ui:border-line ui:px-6 ui:py-16 ui:text-center ui:text-lg ui:text-slate-600">No articles yet.</p>
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
