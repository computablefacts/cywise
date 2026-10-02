{{--
  Page title block.

  <x-ui.page-header title="Tableau de bord" subtitle="...">
    <x-slot:actions>...</x-slot:actions>
  </x-ui.page-header>
--}}
@props([
  'title',
  'subtitle' => null,
])

<div {{ $attributes->merge(['class' => 'ui:flex ui:flex-wrap ui:items-end ui:justify-between ui:gap-4']) }}>
  <div class="ui:min-w-0">
    <h1 class="ui:m-0 ui:text-2xl ui:font-bold ui:tracking-tight ui:text-ink ui:leading-8">{{ $title }}</h1>
    @if($subtitle)
      <p class="ui:m-0 ui:mt-1 ui:text-sm ui:text-slate-500">{{ $subtitle }}</p>
    @endif
  </div>
  @isset($actions)
    <div class="ui:flex ui:items-center ui:gap-2">{{ $actions }}</div>
  @endisset
</div>
