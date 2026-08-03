@props([
    'href' => '',
    'icon' => 'phosphor-house-duotone',
    'active' => false,
    'hideUntilGroupHover' => true,
    'target' => '_self',
    'ajax' => true
])

@php
    $isActive = filter_var($active, FILTER_VALIDATE_BOOLEAN);
@endphp

<a {{ $attributes }} href="{{ $href }}" @if((($href ?? false) && $target == '_self') && $ajax) @else @if($ajax) target="_blank" @endif @endif class="@if($isActive){{ 'text-dark border-secondary shadow-sm bg-white fw-medium' }}@else{{ 'border-transparent' }}@endif transition-colors px-3 py-2 d-flex rounded w-100 small hover-bg-light justify-content-start align-items-center hover-text-dark gap-2 overflow-hidden">
    <x-dynamic-component :component="$icon" class="flex-shrink-0" style="width: 1.25rem; height: 1.25rem;" />
    <span class="flex-shrink-0 transition-all">{{ $slot }}</span>
</a>
