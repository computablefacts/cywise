<?php

use App\Http\Controllers\Iframes\VulnerabilitiesController;
use App\Http\Middleware\CheckPermissionsHttpRequest;
use App\Http\Middleware\LogHttpRequests;
use Illuminate\Http\Request;
use function Laravel\Folio\{middleware, name, render};

middleware([LogHttpRequests::class, 'auth', CheckPermissionsHttpRequest::class]);
name('vulnerabilities');
render(function (Request $request) {
  return app(VulnerabilitiesController::class)($request);
});
?>

<x-layouts.app>
  @php
    // Timeline items (date → time → events) flattened, most critical first.
    $rows = collect($items)
      ->flatMap(fn($times) => collect($times)->flatMap(fn($events) => $events))
      ->sortBy([['_severity', 'asc'], ['timestamp', 'desc']])
      ->values();

    $level = request('level');
    $filters = request()->only(['tld', 'tags', 'port_tags', 'asset_id']);
    $isFiltered = !empty(array_filter($filters)) || !empty($level);

    // KPI tiles double as severity filters; clicking the active one clears it.
    $kpis = [
      ['level' => 'high', 'tone' => 'high', 'value' => $nb_high, 'label' => __('High vulnerabilities')],
      ['level' => 'medium', 'tone' => 'medium', 'value' => $nb_medium, 'label' => __('Medium vulnerabilities')],
      ['level' => 'low', 'tone' => 'low', 'value' => $nb_low, 'label' => __('Low vulnerabilities')],
    ];
  @endphp

  <div class="ui:mx-auto ui:flex ui:w-full ui:max-w-7xl ui:flex-col ui:gap-6 ui:px-4 ui:py-8 ui:lg:px-8">

    <x-ui.page-header :title="__('Vulnerabilities')"
                      :subtitle="__('Weaknesses found on your exposed assets, most critical first.')"/>

    <div class="ui:grid ui:grid-cols-1 ui:gap-4 ui:sm:grid-cols-3">
      @foreach($kpis as $kpi)
        <x-ui.stat :tone="$kpi['tone']" icon="warning-octagon" :value="$kpi['value']" :label="$kpi['label']"
                   :href="route('vulnerabilities', $level === $kpi['level'] ? $filters : array_merge($filters, ['level' => $kpi['level']]))"
                   :class="$level === $kpi['level'] ? 'ui:ring-2 ui:ring-brand-500 ui:border-transparent!' : ''"/>
      @endforeach
    </div>

    {{-- Ctrl+F like search: asset, tag (user or system), CVE, date… --}}
    <x-ui.search target="#vulnerabilities-list" :reset="$isFiltered ? route('vulnerabilities') : null"/>

    {{-- List --}}
    <x-ui.card id="vulnerabilities-list" flush :title="trans_choice(':count vulnerability|:count vulnerabilities', $rows->count(), ['count' => $rows->count()])"
               :subtitle="$rows->isEmpty() ? null : __('Click a row to see the problem, the solution and the available actions.')">
      @if($rows->isEmpty())
        <x-ui.empty>
          {{ $isFiltered ? __('No vulnerability matches these filters.') : __('Good job! No vulnerabilities to fix.') }}
        </x-ui.empty>
      @else
        <ul class="ui:m-0 ui:list-none ui:p-0">
          @foreach($rows as $row)
            {!! $row['html'] !!}
          @endforeach
        </ul>
        <x-ui.empty icon="magnifying-glass" data-search-empty style="display: none">{{ __('No result for this search.') }}</x-ui.empty>
      @endif
    </x-ui.card>
  </div>

  @include('theme::iframes.timeline._share-modal')
  @include('theme::iframes._scripts')
</x-layouts.app>
