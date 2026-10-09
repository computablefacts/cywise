{{--
  Website call to action (link).

  <x-site.button :href="route('register')">Commencer →</x-site.button>
  <x-site.button variant="outline" size="md" :href="…">…</x-site.button>
--}}
@props([
  'href',
  'variant' => 'primary', // primary | dark | outline | light
  'size' => 'lg',         // md | lg
])

@php
  $sizes = match ($size) {
    'md' => 'ui:h-11 ui:px-5 ui:text-[15px]',
    default => 'ui:h-14 ui:px-7 ui:text-[17px]',
  };

  // "!" beats the legacy global "a" colour rules
  $variants = match ($variant) {
    'dark' => 'ui:bg-ink ui:text-white! ui:hover:bg-slate-800',
    'outline' => 'ui:border-2 ui:border-solid ui:border-ink ui:text-ink! ui:hover:bg-ink ui:hover:text-white!',
    'light' => 'ui:bg-white ui:text-ink! ui:hover:bg-brand-50',
    default => 'ui:bg-brand-500 ui:text-white! ui:hover:bg-brand-600',
  };
@endphp

<a href="{{ $href }}" {{ $attributes->merge(['class' => "ui:inline-flex ui:items-center ui:justify-center ui:gap-2 ui:rounded-[14px] ui:font-display ui:font-bold ui:uppercase ui:no-underline! ui:transition-colors ui:focus-visible:outline-2 ui:focus-visible:outline-offset-2 ui:focus-visible:outline-brand-500 $sizes $variants"]) }}>
  {{ $slot }}
</a>
