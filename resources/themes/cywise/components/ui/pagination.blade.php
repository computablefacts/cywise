{{--
  Server-side page navigation. Hidden when everything fits on one page.

    [« First] [‹ Previous]   3 / 12   [Next ›] [Last »]

  <x-ui.pagination :page="$currentPage" :pages="$nbPages" :url="fn($page) => route('documents', ['page' => $page])"/>
--}}
@props([
  'page',
  'pages',
  'url', // closure: page number → href
])

@php
  $firstPage = 1;
  $hasPrevious = $page > $firstPage;
  $hasNext = $page < $pages;
@endphp

@if($pages > $firstPage)
  <nav aria-label="{{ __('Pagination') }}"
    {{ $attributes->merge(['class' => 'ui:flex ui:flex-wrap ui:items-center ui:justify-center ui:gap-2 ui:px-5 ui:py-3']) }}>
    <x-ui.button variant="secondary" size="sm" :href="$hasPrevious ? $url($firstPage) : null" :disabled="!$hasPrevious">
      <x-phosphor-caret-double-left class="ui:size-4"/>
      {{ __('First') }}
    </x-ui.button>
    <x-ui.button variant="secondary" size="sm" :href="$hasPrevious ? $url($page - 1) : null" :disabled="!$hasPrevious">
      <x-phosphor-caret-left class="ui:size-4"/>
      {{ __('Previous') }}
    </x-ui.button>

    <span class="ui:px-2 ui:text-sm ui:tabular-nums ui:text-slate-500">{{ $page }} / {{ $pages }}</span>

    <x-ui.button variant="secondary" size="sm" icon="caret-right" :href="$hasNext ? $url($page + 1) : null" :disabled="!$hasNext">
      {{ __('Next') }}
    </x-ui.button>
    <x-ui.button variant="secondary" size="sm" icon="caret-double-right" :href="$hasNext ? $url($pages) : null" :disabled="!$hasNext">
      {{ __('Last') }}
    </x-ui.button>
  </nav>
@endif
