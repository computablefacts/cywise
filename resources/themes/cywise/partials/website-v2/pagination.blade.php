@php
    // 44px squares: current page ink filled, links bordered, disabled arrows faded
    $item = 'ui:inline-flex ui:h-11 ui:min-w-11 ui:items-center ui:justify-center ui:rounded-xl ui:px-3 ui:font-mono ui:text-sm';
    $link = 'ui:border ui:border-solid ui:border-line ui:text-ink! ui:no-underline! ui:transition-colors ui:hover:border-ink';
    $current = 'ui:bg-ink ui:text-white';
    $disabled = 'ui:border ui:border-solid ui:border-line ui:text-slate-300';
@endphp

@if ($paginator->hasPages())
    <nav aria-label="Pagination" class="ui:mt-12 ui:flex ui:flex-wrap ui:items-center ui:justify-center ui:gap-2">
        @if ($paginator->onFirstPage())
            <span aria-disabled="true" class="{{ $item }} {{ $disabled }}">←</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="{{ $item }} {{ $link }}">←</a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="{{ $item }} ui:text-slate-500">{{ $element }}</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page === $paginator->currentPage())
                        <span aria-current="page" class="{{ $item }} {{ $current }}">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="{{ $item }} {{ $link }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="{{ $item }} {{ $link }}">→</a>
        @else
            <span aria-disabled="true" class="{{ $item }} {{ $disabled }}">→</span>
        @endif
    </nav>
@endif
