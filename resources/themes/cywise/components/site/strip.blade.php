{{--
  Severity strip: the app's row edge (critical → medium → low → brand), used as the site's signature.

  <x-site.strip/>            critical first (top of page)
  <x-site.strip reverse/>    brand first (bottom of page)
--}}
@props([
  'reverse' => false,
])

<div aria-hidden="true" {{ $attributes->merge(['class' => 'ui:flex ui:h-1.5' . ($reverse ? ' ui:flex-row-reverse' : '')]) }}>
  <span class="ui:flex-1 ui:bg-critical"></span>
  <span class="ui:flex-1 ui:bg-medium"></span>
  <span class="ui:flex-1 ui:bg-low"></span>
  <span class="ui:flex-1 ui:bg-info"></span>
  <span class="ui:flex-1 ui:bg-ink"></span>
</div>
