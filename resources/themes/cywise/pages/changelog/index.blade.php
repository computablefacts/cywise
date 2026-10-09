<?php
    use function Laravel\Folio\{name};
    name('changelogs');

    $logs = \Wave\Changelog::orderBy('created_at', 'desc')->paginate(10);

    // use a dynamic layout based on whether or not the user is authenticated
    $layout = ((auth()->guest()) ? 'layouts.website-v2' : 'layouts.app');
?>

@php
    // The app layout already wraps the page in <main>
    $tag = $layout === 'layouts.website-v2' ? 'main' : 'div';
@endphp

<x-dynamic-component 
	:component="$layout"
  :seo="[
        'title' => __('Changelog'),
        'description' => __('Latest updates and enhancements'),
        'type' => 'website',
    ]"
>
    <{{ $tag }} class="ui:mx-auto ui:w-full ui:max-w-[1240px] ui:px-4 ui:pb-24 ui:pt-16 ui:sm:px-6">
        <x-site.display as="h1" size="xl" class="ui:text-[clamp(30px,4.2vw,54px)]!">{{ __('Changelog') }}</x-site.display>
        <p class="ui:m-0 ui:mt-6 ui:max-w-[560px] ui:text-xl ui:leading-normal ui:text-slate-700">{{ __('Latest updates and enhancements') }}</p>

        {{-- Timeline: date column, then title and body --}}
        <ol class="ui:m-0 ui:mt-12 ui:list-none ui:p-0">
            @foreach($logs as $changelog)
                <li class="ui:grid ui:grid-cols-1 ui:gap-3 ui:border-0 ui:border-t ui:border-solid ui:border-line ui:py-10 ui:md:grid-cols-[200px_1fr] ui:md:gap-10">
                    <time class="ui:font-mono ui:text-[13px] ui:uppercase ui:text-slate-500" datetime="{{ Carbon\Carbon::parse($changelog->created_at)->toIso8601String() }}">{{ Carbon\Carbon::parse($changelog->created_at)->toFormattedDateString() }}</time>
                    <div class="ui:min-w-0">
                        <a href="{{ route('changelog', ['changelog' => $changelog->id]) }}" class="ui:text-[clamp(22px,2.4vw,28px)] ui:font-extrabold ui:uppercase ui:leading-[1.05] ui:tracking-tight ui:text-ink! ui:no-underline! ui:font-stretch-112% ui:hover:text-brand-700!">{{ $changelog->title }}</a>
                        <div class="
                            ui:mt-4 ui:max-w-[720px] ui:text-[17px] ui:leading-[1.7] ui:text-slate-700 ui:break-words
                            ui:[&>*:first-child]:mt-0
                            ui:[&_p]:m-0 ui:[&_p]:mt-4
                            ui:[&_a]:text-brand-700! ui:[&_a]:underline! ui:[&_a]:underline-offset-4 ui:[&_a:hover]:text-ink!
                            ui:[&_strong]:font-bold ui:[&_strong]:text-ink
                            ui:[&_ul]:m-0 ui:[&_ul]:mt-4 ui:[&_ul]:list-disc ui:[&_ul]:pl-6 ui:[&_ol]:m-0 ui:[&_ol]:mt-4 ui:[&_ol]:list-decimal ui:[&_ol]:pl-6 ui:[&_li]:mt-1.5 ui:[&_li::marker]:text-brand-500
                            ui:[&_code]:rounded-md ui:[&_code]:bg-slate-100 ui:[&_code]:px-1.5 ui:[&_code]:py-0.5 ui:[&_code]:font-mono ui:[&_code]:text-[0.85em]
                            ui:[&_img]:mt-6 ui:[&_img]:h-auto ui:[&_img]:rounded-2xl
                        ">
                            {!! $changelog->body !!}
                        </div>
                    </div>
                </li>
            @endforeach
        </ol>

        {{ $logs->links('theme::partials.website-v2.pagination') }}
    </{{ $tag }}>
</x-dynamic-component>
