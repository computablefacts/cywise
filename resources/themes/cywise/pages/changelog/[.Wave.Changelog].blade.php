<?php
    use function Laravel\Folio\{name};
    name('changelog');
    
    // use a dynamic layout based on whether or not the user is authenticated
    $layout = ((auth()->guest()) ? 'layouts.website-v2' : 'layouts.app');
?>

@php
    // The app layout already wraps the page in <main>
    $tag = $layout === 'layouts.website-v2' ? 'main' : 'div';
@endphp

<x-dynamic-component 
	:component="$layout"
>
    <{{ $tag }} class="ui:mx-auto ui:w-full ui:max-w-[880px] ui:px-4 ui:pb-24 ui:pt-12 ui:sm:px-6">
        <a class="ui:font-mono ui:text-[13px] ui:uppercase ui:text-slate-600! ui:no-underline! ui:hover:text-ink!" href="{{ route('changelogs') }}">← {{ __('View Full Changelog') }}</a>

        <article id="changelog-{{ $changelog->id }}" class="ui:mt-8">

            <meta property="name" content="{{ $changelog->title }}">
            <meta property="author" typeof="Person" content="admin">
            <meta property="dateModified" content="{{ Carbon\Carbon::parse($changelog->updated_at)->toIso8601String() }}">
            <meta class="uk-margin-remove-adjacent" property="datePublished" content="{{ Carbon\Carbon::parse($changelog->created_at)->toIso8601String() }}">

            <x-site.display as="h1" class="ui:text-[clamp(28px,4.4vw,60px)]!">{{ $changelog->title }}</x-site.display>

            @if ($changelog->description)
                <p class="ui:m-0 ui:mt-6 ui:max-w-[720px] ui:text-[22px] ui:leading-normal ui:text-slate-700">{{ $changelog->description }}</p>
            @endif

            <p class="ui:m-0 ui:mt-10 ui:border-0 ui:border-b ui:border-solid ui:border-ink ui:pb-4 ui:font-mono ui:text-[13px] ui:uppercase ui:text-slate-600">
              {!! __('Posted on <time datetime=":datetime">:date</time>', [
              'datetime' => Carbon\Carbon::parse($changelog->created_at)->toIso8601String(),
              'date' => Carbon\Carbon::parse($changelog->created_at)->toFormattedDateString(),
              ]) !!}
            </p>

            {{-- Body: HTML from the DB, styled through descendant selectors --}}
            <x-site.prose class="ui:mt-12">
                {!! $changelog->body !!}
            </x-site.prose>

        </article>
    </{{ $tag }}>
</x-dynamic-component>
