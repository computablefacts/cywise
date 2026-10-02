{{--
  Surface with optional header.

  <x-ui.card title="Top 5" :href="route('vulnerabilities')">...</x-ui.card>

  - href: adds a "see all" chevron link in the header.
  - flush: removes body padding (tables, lists) and clips rows to the rounded corners.
--}}
@props([
  'title' => null,
  'subtitle' => null,
  'href' => null,
  'flush' => false,
])

<section {{ $attributes->merge(['class' => 'ui:flex ui:flex-col ui:rounded-xl ui:border ui:border-solid ui:border-line ui:bg-surface ui:shadow-xs ui:min-w-0' . ($flush ? ' ui:overflow-hidden' : '')]) }}>
  @if($title)
    <header class="ui:flex ui:items-start ui:justify-between ui:gap-4 ui:px-5 ui:pt-4 {{ $flush ? 'ui:pb-3' : '' }}">
      <div class="ui:min-w-0">
        <h2 class="ui:m-0 ui:text-base ui:font-semibold ui:text-ink ui:leading-6">{{ $title }}</h2>
        @if($subtitle)
          <p class="ui:m-0 ui:mt-0.5 ui:text-sm ui:text-slate-500">{{ $subtitle }}</p>
        @endif
      </div>
      @if($href)
        <a href="{{ $href }}"
           class="ui:flex ui:size-7 ui:shrink-0 ui:items-center ui:justify-center ui:rounded-md ui:text-slate-400! ui:hover:text-ink! ui:hover:bg-slate-100 ui:no-underline!"
           aria-label="{{ __('See all') }}">
          <x-phosphor-caret-right class="ui:size-4"/>
        </a>
      @endif
    </header>
  @endif
  <div class="{{ $flush ? '' : 'ui:p-5' }} {{ $title && !$flush ? 'ui:pt-3' : '' }} ui:flex-1 ui:min-w-0">
    {{ $slot }}
  </div>
</section>
