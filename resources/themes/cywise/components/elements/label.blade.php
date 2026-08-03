@props([
    'for' => ''
])

<label for="{{ $for }}" {{ $attributes->merge(['class' => 'form-label d-inline-flex align-items-center gap-2']) }}>
    <span class="small fw-bold text-dark">
        {{ $slot }}
    </span>
</label>