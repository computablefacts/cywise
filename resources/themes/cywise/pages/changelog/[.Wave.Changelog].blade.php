<?php
    use function Laravel\Folio\{name};
    name('changelog');
    
    // use a dynamic layout based on whether or not the user is authenticated
    $layout = ((auth()->guest()) ? 'layouts.marketing' : 'layouts.app');
?>

<x-dynamic-component 
	:component="$layout"
>
    
    <x-app.container>
        <div class="card mt-3">
            <div class="card-body p-4">

            <x-elements.back-button
                text="{{ __('View Full Changelog') }}"
                :href="route('changelogs')"
            />

            <article id="changelog-{{ $changelog->id }}" class="mt-3">

                <meta property="name" content="{{ $changelog->title }}">
                <meta property="author" typeof="Person" content="admin">
                <meta property="dateModified" content="{{ Carbon\Carbon::parse($changelog->updated_at)->toIso8601String() }}">
                <meta class="uk-margin-remove-adjacent" property="datePublished" content="{{ Carbon\Carbon::parse($changelog->created_at)->toIso8601String() }}">

                <x-app.heading
                    :title="$changelog->title"
                    :description="$changelog->description"
                />

                <p class="mt-3 small fw-bold text-muted text-uppercase tracking-wider">
                  {!! __('Posted on <time datetime=":datetime">:date</time>', [
                  'datetime' => Carbon\Carbon::parse($changelog->created_at)->toIso8601String(),
                  'date' => Carbon\Carbon::parse($changelog->created_at)->toFormattedDateString(),
                  ]) !!}
                </p>
                <div class="mt-3 text-muted">
                    {!! $changelog->body !!}
                </div>

            </article>
            </div>
        </div>
    </x-app.container>

</x-dynamic-component>
