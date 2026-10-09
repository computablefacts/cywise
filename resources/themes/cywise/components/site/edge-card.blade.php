{{--
  Card with a thick colour edge, like the app's severity rows.
  Each solution keeps its tone everywhere on the site:

    attack surface → critical   vulnerabilities → medium   credentials → low
    CyberBuddy     → brand      PSSI            → info     pentest     → ink

  <x-site.edge-card tone="critical" :href="route('website.solutions.attack-surface')">…</x-site.edge-card>
--}}
@props([
  'tone' => 'brand', // critical | medium | low | brand | info | ink | line
  'href' => null,
])

@php
  // Literal class names: Tailwind only generates classes it finds in the sources
  $edge = match ($tone) {
    'critical' => 'ui:bg-critical',
    'medium' => 'ui:bg-medium',
    'low' => 'ui:bg-low',
    'info' => 'ui:bg-info',
    'ink' => 'ui:bg-ink',
    'line' => 'ui:bg-line',
    default => 'ui:bg-brand-500',
  };
  $tag = $href ? 'a' : 'article';
@endphp

<{{ $tag }} @if($href) href="{{ $href }}" @endif
  {{ $attributes->merge(['class' => 'ui:group ui:flex ui:overflow-hidden ui:rounded-2xl ui:border ui:border-solid ui:border-line ui:bg-white ui:text-ink! ui:no-underline! ui:transition-colors' . ($href ? ' ui:hover:border-slate-300' : '')]) }}>
  <span class="ui:w-2.5 ui:shrink-0 {{ $edge }}" aria-hidden="true"></span>
  <span class="ui:flex ui:min-w-0 ui:flex-1 ui:flex-col ui:gap-2 ui:p-6">{{ $slot }}</span>
</{{ $tag }}>
