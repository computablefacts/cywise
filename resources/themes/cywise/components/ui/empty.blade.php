{{--
  Empty state inside a card.

  <x-ui.empty icon="check-circle">Aucune vulnérabilité à corriger.</x-ui.empty>
--}}
@props([
  'icon' => 'check-circle',
])

<div {{ $attributes->merge(['class' => 'ui:flex ui:flex-col ui:items-center ui:justify-center ui:gap-2 ui:py-8 ui:text-center']) }}>
  <span class="ui:flex ui:size-10 ui:items-center ui:justify-center ui:rounded-full ui:bg-low-soft ui:text-low">
    <x-dynamic-component :component="'phosphor-' . $icon" class="ui:size-5"/>
  </span>
  <p class="ui:m-0 ui:text-sm ui:text-slate-500">{{ $slot }}</p>
</div>
