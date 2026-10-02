{{--
  Button or link styled as a button.

  <x-ui.button variant="primary" icon="arrow-right" href="/assets">Voir</x-ui.button>

  Colors on <a> use "!" to beat BlueprintJS' global "a:hover" rule.
--}}
@props([
  'variant' => 'primary', // primary | secondary | ghost
  'size' => 'md',         // sm | md
  'href' => null,
  'icon' => null,         // phosphor icon name, rendered after the label
  'type' => 'button',
])

@php
  $base = 'ui:inline-flex ui:items-center ui:justify-center ui:gap-2 ui:rounded-lg ui:font-medium ui:whitespace-nowrap ui:no-underline! ui:transition-colors ui:cursor-pointer ui:focus-visible:outline-2 ui:focus-visible:outline-offset-2 ui:focus-visible:outline-brand-500 ui:disabled:opacity-50 ui:disabled:cursor-not-allowed';

  $sizes = match ($size) {
    'sm' => 'ui:h-8 ui:px-3 ui:text-xs',
    default => 'ui:h-10 ui:px-4 ui:text-sm',
  };

  $variants = match ($variant) {
    'secondary' => 'ui:bg-white ui:text-ink! ui:hover:text-ink! ui:border ui:border-solid ui:border-line ui:hover:bg-slate-50 ui:shadow-xs',
    'ghost' => 'ui:bg-transparent ui:text-slate-600! ui:hover:text-ink! ui:border-0 ui:hover:bg-slate-100',
    default => 'ui:bg-brand-500 ui:text-white! ui:hover:text-white! ui:border-0 ui:hover:bg-brand-600 ui:shadow-xs',
  };

  $classes = "$base $sizes $variants";
@endphp

@if($href)
  <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
    @if($icon)
      <x-dynamic-component :component="'phosphor-' . $icon" class="ui:size-4 ui:shrink-0"/>
    @endif
  </a>
@else
  <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
    @if($icon)
      <x-dynamic-component :component="'phosphor-' . $icon" class="ui:size-4 ui:shrink-0"/>
    @endif
  </button>
@endif
