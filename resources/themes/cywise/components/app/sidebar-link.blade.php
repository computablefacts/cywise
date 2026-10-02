@props([
    'href' => '',
    'icon' => 'phosphor-house',
    'active' => false,
    'target' => '_self',
    'ajax' => true
])

@php
    $isActive = filter_var($active, FILTER_VALIDATE_BOOLEAN);

    // Colors use "!" to beat BlueprintJS' global "a:hover" rule.
    $state = $isActive
        ? 'ui:bg-brand-50 ui:text-brand-600! ui:hover:text-brand-600! ui:font-medium'
        : 'ui:text-slate-600! ui:hover:text-ink! ui:hover:bg-slate-100';
@endphp

<a {{ $attributes }} href="{{ $href }}" @if((($href ?? false) && $target == '_self') && $ajax) @else @if($ajax) target="_blank" @endif @endif
   @if($isActive) aria-current="page" @endif
   class="{{ $state }} ui:flex ui:w-full ui:items-center ui:gap-3 ui:rounded-lg ui:px-3 ui:py-2 ui:text-sm ui:no-underline! ui:transition-colors">
    <x-dynamic-component :component="$icon" class="ui:size-5 ui:shrink-0"/>
    <span class="ui:truncate">{{ $slot }}</span>
</a>
