{{--
  KPI tile, compact: [icon] [value] [label / hint]. Whole tile is a link when href is set.
  Fixed height (icon size = label + hint lines): tiles align whether or not they have a hint.

  <x-ui.stat tone="high" icon="warning" :value="4" label="Vulnérabilités haute" :href="..."/>
--}}
@props([
  'tone' => 'brand', // brand | high | medium | low | info | neutral
  'icon' => 'info',
  'value' => 0,
  'label' => '',
  'hint' => null,
  'href' => null,
])

@php
  $iconTone = match ($tone) {
    'high' => 'ui:bg-high-soft ui:text-high',
    'medium' => 'ui:bg-medium-soft ui:text-medium',
    'low' => 'ui:bg-low-soft ui:text-low',
    'info' => 'ui:bg-info-soft ui:text-info',
    'neutral' => 'ui:bg-slate-100 ui:text-slate-500',
    default => 'ui:bg-brand-50 ui:text-brand-500',
  };
  $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }} @if($href) href="{{ $href }}" @endif
  {{ $attributes->merge(['class' => 'ui:group ui:flex ui:items-center ui:gap-3 ui:rounded-xl ui:border ui:border-solid ui:border-line ui:bg-surface ui:px-4 ui:py-3 ui:shadow-xs ui:no-underline! ui:transition-colors ui:hover:border-slate-300']) }}>
  <span class="ui:flex ui:size-9 ui:shrink-0 ui:items-center ui:justify-center ui:rounded-full {{ $iconTone }}">
    <x-dynamic-component :component="'phosphor-' . $icon . '-bold'" class="ui:size-4"/>
  </span>
  <span class="ui:text-2xl ui:font-bold ui:leading-none ui:text-ink! ui:tabular-nums">{{ $value }}</span>
  <span class="ui:flex ui:min-w-0 ui:flex-col">
    <span class="ui:text-sm ui:leading-5 ui:text-slate-500! ui:group-hover:text-ink!">{{ $label }}</span>
    @if($hint)
      <span class="ui:truncate ui:text-xs ui:leading-4 ui:text-slate-400!">{{ $hint }}</span>
    @endif
  </span>
</{{ $tag }}>
