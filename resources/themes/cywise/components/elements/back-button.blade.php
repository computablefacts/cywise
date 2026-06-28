<div {{ $attributes->merge(['class' => 'mx-auto w-100']) }}>
    <a href="{{ $href ?? '' }}" class="btn btn-sm btn-light border rounded-pill mb-3 text-primary d-inline-flex align-items-center">
        <svg class="me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" style="width: 14px; height: 14px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
        {{ $text ?? '' }}
    </a>
</div>