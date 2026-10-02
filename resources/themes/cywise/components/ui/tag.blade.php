{{--
  Chip (asset tags, port tags). Optional slot "remove" renders a trailing button.
  Keep in sync with the chip built in JS by createTagChip (iframes/_scripts), palette included.

  tone="auto" picks a stable color from the text: "nginx" is always the same hue.

  <x-ui.tag tone="auto">nginx</x-ui.tag>
--}}
@props([
  'tone' => 'neutral', // neutral | auto
])

@php
  // Literal class names: Tailwind only generates classes it finds in the sources
  $hues = [
    'ui:bg-blue-50 ui:text-blue-700',
    'ui:bg-violet-50 ui:text-violet-700',
    'ui:bg-emerald-50 ui:text-emerald-700',
    'ui:bg-amber-50 ui:text-amber-800',
    'ui:bg-rose-50 ui:text-rose-700',
    'ui:bg-cyan-50 ui:text-cyan-800',
    'ui:bg-indigo-50 ui:text-indigo-700',
    'ui:bg-lime-50 ui:text-lime-800',
  ];
  $color = $tone === 'auto'
    ? $hues[crc32(trim(strip_tags($slot))) % count($hues)]
    : 'ui:bg-slate-100 ui:text-slate-700';
@endphp

<span {{ $attributes->merge(['class' => "ui:inline-flex ui:items-center ui:gap-1 ui:rounded-md ui:px-2 ui:py-0.5 ui:text-xs ui:font-medium $color"]) }}>
  <span>{{ $slot }}</span>
  {{ $remove ?? '' }}
</span>
