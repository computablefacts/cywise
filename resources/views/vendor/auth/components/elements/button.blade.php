{{--
  Full-width action, website style. Keeps the package API (type, tag, href, submit) and the wire:loading spinner.
--}}
@props([
    'type' => 'primary',
    'size' => 'md',
    'tag' => 'button',
    'href' => '/',
    'submit' => false,
    'rounded' => 'full'
])

@php
    $typeClasses = match ($type) {
        'secondary' => 'ui:border ui:border-solid ui:border-line ui:bg-white ui:text-ink! ui:hover:bg-slate-50',
        'success' => 'ui:bg-low ui:text-white! ui:hover:bg-emerald-600',
        'info' => 'ui:bg-info ui:text-white! ui:hover:bg-blue-600',
        'warning' => 'ui:bg-medium ui:text-white! ui:hover:bg-amber-600',
        'danger' => 'ui:bg-critical ui:text-white! ui:hover:bg-red-700',
        default => 'auth-component-button ui:bg-brand-500 ui:text-white! ui:hover:bg-brand-600',
    };
    $loadingTarget = $attributes['wire:target'];

    // Slot text comes from config('devdojo.auth.language'): translate plain-text slots
    $text = trim($slot->toHtml());
    $label = $text === strip_tags($text) ? __(html_entity_decode($text, ENT_QUOTES)) : null;

    $classes = 'ui:inline-flex ui:h-12 ui:w-full ui:cursor-pointer ui:items-center ui:justify-center ui:gap-2 ui:rounded-[14px] ui:border-0 ui:font-display ui:text-[15px] ui:font-bold ui:uppercase ui:no-underline! ui:transition-colors ui:focus-visible:outline-2 ui:focus-visible:outline-offset-2 ui:focus-visible:outline-brand-500 ui:disabled:cursor-not-allowed ui:disabled:opacity-50 ' . $typeClasses;
@endphp

@if($tag === 'a')
    <a href="{{ $href }}" {{ $attributes->except(['class']) }} class="{{ $classes }}">{{ $label ?? $slot }}</a>
@else
    <button type="{{ $submit ? 'submit' : 'button' }}" {{ $attributes->except(['class']) }} class="{{ $classes }}">
        <svg xmlns="http://www.w3.org/2000/svg" wire:loading @if(isset($loadingTarget)) wire:target="{{ $loadingTarget }}" @endif viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ui:size-4 ui:animate-spin"><path d="M21 12a9 9 0 1 1-6.219-8.56"></path></svg>
        {{ $label ?? $slot }}
    </button>
@endif
