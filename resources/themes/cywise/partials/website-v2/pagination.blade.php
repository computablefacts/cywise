@if ($paginator->hasPages())
    <nav aria-label="Pagination" class="blog-pagination">
        @if ($paginator->onFirstPage())
            <span aria-disabled="true">←</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev">←</a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span>{{ $element }}</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page === $paginator->currentPage())
                        <span aria-current="page" class="active">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next">→</a>
        @else
            <span aria-disabled="true">→</span>
        @endif
    </nav>
@endif
