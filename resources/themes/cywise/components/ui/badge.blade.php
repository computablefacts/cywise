{{--
  Soft label (status, source, counts).

  <x-ui.badge level="high">{{ __('High') }}</x-ui.badge>
--}}
@props([
  'level' => 'neutral', // critical | high | medium | low | info | neutral
])

@php
  $tone = match ($level) {
    'critical' => 'ui:bg-critical-soft ui:text-critical ui:ring-red-200',
    'high' => 'ui:bg-high-soft ui:text-red-600 ui:ring-red-200',
    'medium' => 'ui:bg-medium-soft ui:text-amber-700 ui:ring-amber-200',
    'low' => 'ui:bg-low-soft ui:text-emerald-700 ui:ring-emerald-200',
    'info' => 'ui:bg-info-soft ui:text-blue-700 ui:ring-blue-200',
    default => 'ui:bg-slate-100 ui:text-slate-600 ui:ring-slate-200',
  };
@endphp

<span {{ $attributes->merge(['class' => "ui:inline-flex ui:items-center ui:rounded-md ui:px-2 ui:py-0.5 ui:text-xs ui:font-medium ui:ring-1 ui:ring-inset ui:whitespace-nowrap $tone"]) }}>
  {{ $slot }}
</span>
