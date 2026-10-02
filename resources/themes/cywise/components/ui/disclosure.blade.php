{{--
  Collapsible section, closed by default. Native <details>: no JS needed.

  <x-ui.disclosure icon="terminal-window" title="Protéger un serveur" subtitle="...">...</x-ui.disclosure>
--}}
@props([
  'title',
  'subtitle' => null,
  'icon' => null,
])

<details {{ $attributes->merge(['class' => 'ui:group ui:rounded-xl ui:border ui:border-solid ui:border-line ui:bg-surface ui:shadow-xs']) }}>
  <summary class="ui:flex ui:cursor-pointer ui:list-none ui:items-center ui:gap-4 ui:px-5 ui:py-4 ui:select-none ui:[&::-webkit-details-marker]:hidden">
    @if($icon)
      <span class="ui:flex ui:size-9 ui:shrink-0 ui:items-center ui:justify-center ui:rounded-lg ui:bg-slate-100 ui:text-slate-600">
        <x-dynamic-component :component="'phosphor-' . $icon" class="ui:size-5"/>
      </span>
    @endif
    <span class="ui:flex ui:min-w-0 ui:flex-1 ui:flex-col">
      <span class="ui:text-sm ui:font-semibold ui:text-ink">{{ $title }}</span>
      @if($subtitle)
        <span class="ui:text-sm ui:text-slate-500">{{ $subtitle }}</span>
      @endif
    </span>
    <x-phosphor-caret-down class="ui:size-4 ui:shrink-0 ui:text-slate-400 ui:transition-transform ui:group-open:rotate-180"/>
  </summary>
  <div class="ui:border-0 ui:border-t ui:border-solid ui:border-line ui:px-5 ui:py-5">
    {{ $slot }}
  </div>
</details>
