@php
    $locale = app()->getLocale() === 'en' ? 'en' : 'fr';
@endphp

<x-layouts.website-v2
    :locale="$locale"
    :seo="[
        'title' => $page->title . ' — Cywise',
        'description' => $page->meta_description ?? $page->excerpt ?? '',
    ]"
>
    <main>
        <article class="ui:mx-auto ui:max-w-[880px] ui:px-4 ui:pb-24 ui:pt-12 ui:sm:px-6">
            <header>
                <a class="ui:font-mono ui:text-[13px] ui:uppercase ui:text-slate-600! ui:no-underline! ui:hover:text-ink!" href="{{ route($locale === 'en' ? 'website.en.home' : 'home') }}">
                    {{ $locale === 'en' ? '← Back home' : '← Retour à l’accueil' }}
                </a>

                <x-site.display as="h1" class="ui:mt-8 ui:text-[clamp(28px,4.4vw,60px)]!">{{ $page->title }}</x-site.display>

                @if ($page->excerpt)
                    <p class="ui:m-0 ui:mt-6 ui:max-w-[720px] ui:text-[22px] ui:leading-normal ui:text-slate-700">{{ $page->excerpt }}</p>
                @endif

                {{-- Meta row on a thin ink rule: author · last update --}}
                <p class="ui:m-0 ui:mt-10 ui:flex ui:flex-wrap ui:gap-x-3 ui:gap-y-1 ui:border-0 ui:border-b ui:border-solid ui:border-ink ui:pb-4 ui:font-mono ui:text-[13px] ui:uppercase ui:text-slate-600">
                    <span>{{ $page->author?->name ?? 'Cywise' }}</span>
                    <span aria-hidden="true">·</span>
                    <span>{{ $page->updated_at->format('d.m.Y') }}</span>
                </p>
            </header>

            @if ($page->image)
                <img class="ui:mt-10 ui:h-auto ui:w-full ui:rounded-3xl" src="{{ $page->image() }}" alt="{{ $page->title }}">
            @endif

            {{-- Body: HTML from the DB, styled through descendant selectors --}}
            <x-site.prose class="ui:mt-12">
                {!! $page->body !!}
            </x-site.prose>
        </article>
    </main>
</x-layouts.website-v2>
