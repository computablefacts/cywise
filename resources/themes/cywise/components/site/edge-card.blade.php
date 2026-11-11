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
  'variant' => 'edge', // edge | flat
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

  $baseClasses = 'ui:group ui:flex ui:overflow-hidden ui:rounded-2xl ui:text-ink! ui:no-underline! ui:transition-colors';
  
  if ($variant === 'flat') {
    $classes = $baseClasses . ' ui:bg-slate-100 ui:hover:bg-slate-200 ui:p-6 ui:min-h-52 ui:flex-col ui:gap-2.5';
  } else {
    $classes = $baseClasses . ' ui:border ui:border-solid ui:border-line ui:bg-white' . ($href ? ' ui:hover:border-slate-300' : '');
  }

  $tag = $href ? 'a' : 'article';
@endphp

<{{ $tag }} @if($href) href="{{ $href }}" @endif
  {{ $attributes->merge(['class' => $classes]) }}>
  @if($variant === 'edge')
    <span class="ui:w-2.5 ui:shrink-0 {{ $edge }}" aria-hidden="true"></span>
    <span class="ui:flex ui:min-w-0 ui:flex-1 ui:flex-col ui:gap-2 ui:p-6">{{ $slot }}</span>
  @else
    {{ $slot }}
  @endif
</{{ $tag }}>
