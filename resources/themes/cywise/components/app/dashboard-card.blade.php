<a href="{{ $href ?? '' }}" @if($target ?? false) target="_blank" @else @endif class="card d-flex flex-row overflow-hidden position-relative p-3 w-100 text-decoration-none transition-transform hover-scale-sm">
    <div class="card-body d-flex flex-column justify-content-center align-items-start p-0">
        <h5 class="card-title fw-bold mb-1 text-dark">{{ $title ?? '' }}</h5>
        <p class="card-text small text-muted mb-2">{{ $description ?? '' }}</p>
        <div class="d-inline-flex align-items-center small text-primary gap-1">
            <span>{{ $linkText ?? '' }}</span>
            <svg class="ms-1" fill="none" width="10" height="10" viewBox="0 0 10 10" aria-hidden="true" stroke="currentColor"><path d="M1 1l4 4-4 4"></path></svg>
        </div>
    </div>
    <img src="{{ $image ?? '' }}" class="ms-auto" style="height: 80px; width: auto;">
</a>
