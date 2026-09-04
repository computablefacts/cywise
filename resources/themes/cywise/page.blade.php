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
        <section class="blogpost-hero">
            <div class="container-fluid shell">
                <a class="mono blog-back" href="{{ route($locale === 'en' ? 'website.en.home' : 'home') }}">
                    {{ $locale === 'en' ? '← BACK HOME' : '← RETOUR À L’ACCUEIL' }}
                </a>
                <div class="mono blogpost-kicker">CYWISE / PAGE</div>
                <h1>{{ $page->title }}</h1>

                @if ($page->excerpt)
                    <p class="blogpost-lead">{{ $page->excerpt }}</p>
                @endif

                <div class="blogpost-meta mono">
                    <span>{{ $page->author?->name ?? 'Cywise' }}</span>
                    <span>{{ $page->updated_at->format('d.m.Y') }}</span>
                </div>
            </div>
        </section>

        <section class="blogpost-body section-pad">
            <div class="container-fluid shell">
                @if ($page->image)
                    <img class="blogpost-image" src="{{ $page->image() }}" alt="{{ $page->title }}">
                @endif

                <article class="blogpost-article">
                    {!! $page->body !!}
                </article>
            </div>
        </section>
    </main>
</x-layouts.website-v2>
