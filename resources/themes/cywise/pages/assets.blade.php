<?php

use App\Http\Controllers\Iframes\AssetsController;
use App\Http\Middleware\CheckPermissionsHttpRequest;
use App\Http\Middleware\LogHttpRequests;
use Illuminate\Http\Request;
use function Laravel\Folio\{middleware, name, render};

middleware([LogHttpRequests::class, 'auth', CheckPermissionsHttpRequest::class]);
name('assets');
render(function (Request $request) {
  return app(AssetsController::class)($request);
});
?>

<x-layouts.app>
  @php
    // Timeline items (date → time → assets and servers) flattened, most recent first.
    $rows = collect($items)
      ->flatMap(fn($times) => collect($times)->flatMap(fn($events) => $events))
      ->values();

    $status = request('status');
    $filters = request()->only(['tld', 'tags', 'monitoring_type']);
    $isFiltered = !empty(array_filter($filters)) || !empty($status) || request('asset_id');

    // KPI tiles double as status filters; clicking the active one clears it.
    $kpis = [
      ['status' => null, 'tone' => 'info', 'icon' => 'globe', 'value' => $nb_monitored + $nb_monitorable, 'label' => __('Assets')],
      ['status' => 'monitored', 'tone' => 'low', 'icon' => 'eye', 'value' => $nb_monitored, 'label' => __('Assets Monitored')],
      ['status' => 'monitorable', 'tone' => 'medium', 'icon' => 'eye-slash', 'value' => $nb_monitorable, 'label' => __('Assets Monitorable')],
    ];

    // Tabs: assets (domains, IPs) | agents (servers with the agent installed)
    [$assetRows, $serverRows] = $rows->partition(fn($row) => isset($row['_asset']));
    $tab = request('tab') === 'agents' ? 'agents' : 'assets';

    // Assets grouped by root domain, biggest groups first, then single rows by name (not by date):
    //   acme.example (5 subdomains), example.com (2 subdomains), 10.10.10.0, 203.0.113.10, solo.org
    $entries = $assetRows
      ->groupBy(fn($row) => $row['_asset']->tld() ?? "asset-{$row['_asset']->id}")
      ->map(function ($group, $key) {
        [$roots, $children] = $group->partition(fn($row) => $row['_asset']->asset === $key);
        return [
          'name' => $group->count() > 1 ? $key : $group->first()['_asset']->asset,
          'size' => $group->count(),
          'root' => $roots->first(),
          'children' => $children->values(),
          'row' => $group->first(),
        ];
      })
      ->sort(fn($a, $b) => $b['size'] <=> $a['size'] ?: strnatcasecmp($a['name'], $b['name']))
      ->values();

    $serverRows = $serverRows->sort(fn($a, $b) => strnatcasecmp($a['_server']->name, $b['_server']->name));
  @endphp

  <div class="ui:mx-auto ui:flex ui:w-full ui:max-w-7xl ui:flex-col ui:gap-6 ui:px-4 ui:py-8 ui:lg:px-8">

    <x-ui.page-header :title="__('Inventory')"
                      :subtitle="__('Domains, IP addresses and servers watched by Cywise.')"/>

    <div class="ui:grid ui:grid-cols-1 ui:gap-4 ui:sm:grid-cols-3">
      @foreach($kpis as $kpi)
        @php
          $isActive = $status === $kpi['status'];
        @endphp
        <x-ui.stat :tone="$kpi['tone']" :icon="$kpi['icon']" :value="$kpi['value']" :label="$kpi['label']"
                   :href="route('assets', $isActive || $kpi['status'] === null ? $filters : array_merge($filters, ['status' => $kpi['status']]))"
                   :class="$isActive && $kpi['status'] !== null ? 'ui:ring-2 ui:ring-brand-500 ui:border-transparent!' : ''"/>
      @endforeach
    </div>

    {{-- Ctrl+F like search: domain, IP, tag, Agent / Scanner, date… --}}
    <x-ui.search target="#inventory-list" :placeholder="__('Search: domain, IP, tag, agent, date…')"
                 :reset="$isFiltered ? route('assets') : null"/>

    {{--
      List + detail panel. Rows stay one line; the selected asset's details
      (teleported by iframes/timeline/_asset) show in the panel.

        ┌──────────────────────┬──────────────┐
        │ list                 │ #asset-panel │  lg+: sticky column
        │                      │              │  < lg: bottom sheet
        └──────────────────────┴──────────────┘
    --}}
    <div x-data="{ selected: null, tab: '{{ $tab }}' }" @keydown.escape.window="selected = null"
         class="ui:grid ui:items-start ui:gap-6 ui:lg:grid-cols-[minmax(0,1fr)_22rem]">
      <x-ui.card id="inventory-list" flush>

        {{-- Tabs (switching clears the panel: it shows the other tab's item) --}}
        <div role="tablist" class="ui:flex ui:gap-1 ui:px-3">
          @foreach(['assets' => [__('Assets'), $assetRows->count()], 'agents' => [__('Agents'), $serverRows->count()]] as $id => [$label, $count])
            <button type="button" role="tab" @click="tab = '{{ $id }}'; selected = null" :aria-selected="tab === '{{ $id }}'"
                    :class="tab === '{{ $id }}' ? 'ui:border-brand-500 ui:text-ink' : 'ui:border-transparent ui:text-slate-500 ui:hover:text-ink'"
                    class="ui:flex ui:items-center ui:gap-2 ui:border-0 ui:border-b-2 ui:border-solid ui:bg-transparent ui:px-3 ui:pb-2.5 ui:pt-3.5 ui:text-sm ui:font-semibold ui:cursor-pointer">
              {{ $label }}
              <span data-search-count="#inventory-{{ $id }}" class="ui:rounded-full ui:bg-slate-100 ui:px-1.5 ui:text-xs ui:font-medium ui:tabular-nums ui:text-slate-600">{{ $count }}</span>
            </button>
          @endforeach
        </div>

        <div id="inventory-assets" x-show="tab === 'assets'" role="tabpanel" {{ $tab === 'assets' ? '' : 'x-cloak' }}>
          @if($assetRows->isEmpty())
            <x-ui.empty icon="globe">
              {{ $isFiltered ? __('No asset matches these filters.') : __('No asset yet. Add a domain or an IP address from the top bar.') }}
            </x-ui.empty>
          @else
            <ul class="ui:m-0 ui:list-none ui:p-0">
              @foreach($entries as $entry)
                @if($entry['size'] === 1)
                  {!! $entry['row']['html'] !!}
                  @continue
                @endif
                @include('theme::iframes.timeline._domain', [
                  'tld' => $entry['name'],
                  'root' => $entry['root'],
                  'children' => $entry['children'],
                  'expanded' => $isFiltered,
                ])
              @endforeach
            </ul>
            <x-ui.empty icon="magnifying-glass" data-search-empty style="display: none">{{ __('No result for this search.') }}</x-ui.empty>
          @endif
        </div>

        <div id="inventory-agents" x-show="tab === 'agents'" role="tabpanel" {{ $tab === 'agents' ? '' : 'x-cloak' }}>
          @if($serverRows->isEmpty())
            <x-ui.empty icon="hard-drives">
              {{ __('No agent yet. Install one from the top bar.') }}
            </x-ui.empty>
          @else
            <ul class="ui:m-0 ui:list-none ui:p-0">
              @foreach($serverRows as $row)
                {!! $row['html'] !!}
              @endforeach
            </ul>
            <x-ui.empty icon="magnifying-glass" data-search-empty style="display: none">{{ __('No result for this search.') }}</x-ui.empty>
          @endif
        </div>
      </x-ui.card>

      <aside :class="selected ? 'ui:flex' : 'ui:hidden ui:lg:flex'"
             class="ui:fixed ui:inset-x-0 ui:bottom-0 ui:z-40 ui:max-h-[70vh] ui:flex-col ui:overflow-y-auto ui:rounded-t-xl ui:border ui:border-solid ui:border-line ui:bg-surface ui:shadow-lg ui:lg:sticky ui:lg:top-20 ui:lg:inset-auto ui:lg:z-auto ui:lg:max-h-[calc(100vh-6rem)] ui:lg:rounded-xl ui:lg:shadow-xs">
        <div id="asset-panel" class="ui:contents"></div>
        <p x-show="!selected" class="ui:m-0 ui:p-5 ui:text-sm ui:text-slate-500">
          {{ __('Select an asset to see its details.') }}
        </p>
      </aside>
    </div>
  </div>

  @include('theme::iframes.timeline._share-modal')
  @include('theme::iframes._scripts')
</x-layouts.app>
