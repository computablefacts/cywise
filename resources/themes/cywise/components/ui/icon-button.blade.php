{{--
  Square icon-only button. The title doubles as tooltip and accessible label.

  <x-ui.icon-button icon="arrow-clockwise" :title="__('Restart Scan')" onclick="restartScan(42)"/>
--}}
@props([
  'icon',
  'title',
])

<button type="button" title="{{ $title }}" aria-label="{{ $title }}"
  {{ $attributes->merge(['class' => 'ui:flex ui:size-8 ui:shrink-0 ui:items-center ui:justify-center ui:rounded-lg ui:border ui:border-solid ui:border-line ui:bg-white ui:text-slate-500 ui:cursor-pointer ui:transition-colors ui:hover:bg-slate-100 ui:hover:text-ink']) }}>
  <x-dynamic-component :component="'phosphor-' . $icon" class="ui:size-4"/>
</button>
