<?php
    use function Laravel\Folio\{name};
    name('changelogs');

    $logs = \Wave\Changelog::orderBy('created_at', 'desc')->paginate(10);

    // use a dynamic layout based on whether or not the user is authenticated
    $layout = ((auth()->guest()) ? 'layouts.marketing' : 'layouts.app');
?>

<x-dynamic-component 
	:component="$layout"
  :seo="[
        'title' => __('Changelog'),
        'description' => __('Latest updates and enhancements'),
        'type' => 'website',
    ]"
>
    <x-app.container>
        <div class="card mt-3">
            <div class="card-body p-4">
            <x-app.heading
                title="{{ __('Changelog') }}"
                description="{{ __('Latest updates and enhancements') }}"
            />
        <div class="mt-3">
                @foreach($logs as $changelog)
                    <div class="d-flex flex-column align-items-start gap-3 flex-lg-row gap-lg-4 mb-4">
                        <div class="flex-shrink-0 px-2 py-1 small rounded-pill text-primary bg-primary bg-opacity-10 border border-primary border-opacity-25">
                            <time datetime="{{ Carbon\Carbon::parse($changelog->created_at)->toIso8601String() }}">{{ Carbon\Carbon::parse($changelog->created_at)->toFormattedDateString() }}</time>
                        </div>
                        <div>
                            <a href="{{ route('changelog', ['changelog' => $changelog->id]) }}" class="h5 text-decoration-none hover-underline">{{ $changelog->title }}</a>
                            <div class="mt-2 text-muted">
                                {!! $changelog->body !!}
                            </div>
                            @if(!$loop->last)
                                <hr class="my-4 border-secondary opacity-25">
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        </div>
    </x-app.container>
</x-dynamic-component>