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
        <section class="ui:mx-auto ui:max-w-[1240px] ui:px-4 ui:pb-12 ui:pt-16 ui:sm:px-6">
            <x-site.display as="h1" size="xl" class="ui:text-[clamp(30px,4.2vw,54px)]!">Depuis le lab Cywise.</x-site.display>
            <p class="ui:m-0 ui:mt-6 ui:max-w-[560px] ui:text-xl ui:leading-normal ui:text-slate-700">Des contenus cybersécurité pratiques pour les équipes qui veulent des réponses claires.</p>
        </section>

        <section class="ui:mx-auto ui:max-w-[1240px] ui:px-4 ui:pb-24 ui:sm:px-6">
            @include('theme::partials.website-v2.categories', [
                'categories' => $categories,
                'locale' => 'fr',
            ])

            @if ($posts->isEmpty())
                <p class="ui:m-0 ui:mt-8 ui:rounded-2xl ui:border ui:border-dashed ui:border-line ui:px-6 ui:py-16 ui:text-center ui:text-lg ui:text-slate-600">Aucun article pour le moment.</p>
            @else
                <div class="ui:mt-8 ui:grid ui:grid-cols-1 ui:gap-3 ui:md:grid-cols-2 ui:xl:grid-cols-3">
                    @include('theme::partials.website-v2.posts-loop', [
                        'posts' => $posts,
                        'locale' => 'fr',
                    ])
                </div>
            @endif

            {{ $posts->links('theme::partials.website-v2.pagination') }}
        </section>
    </main>
</x-layouts.website-v2>
