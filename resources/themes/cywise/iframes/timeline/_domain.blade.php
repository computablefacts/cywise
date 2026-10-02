{{--
  Domain group: a root domain (e.g. acme.example) and its subdomains, collapsed.

    > acme.example  3 subdomains · 2 monitored           4 vulnerabilities  >
        (globe) www.acme.example  …                      1 vulnerability    >
        (globe) api.acme.example  …                      3 vulnerabilities  >

  1st click: expands the subdomains and shows the domain in the side panel (auto-monitoring).
  Subdomain click: its own details in the side panel (iframes/timeline/_asset).

  The root asset, when it exists, is rendered hidden: only its panel (teleported) is used.
  Without it, the panel offers to add it: discovered subdomains inherit the auto-monitoring
  flag of their closest parent asset (AssetsDiscoveryListener).
--}}
{{-- Expects: $tld, $root (?array timeline row of the root asset), $children (Collection of timeline rows), $expanded (bool) --}}
@php
  $group = $root ? collect([$root])->concat($children) : $children;
  $alerts = $group->flatMap(fn($row) => $row['_alerts']);
  $nbMonitored = $group->filter(fn($row) => $row['_asset']->is_monitored)->count();

  // Count color follows the worst severity, as in iframes/timeline/_asset
  $countColor = match (true) {
    $alerts->contains(fn($a) => $a->isHigh()) => 'ui:text-red-600',
    $alerts->contains(fn($a) => $a->isMedium()) => 'ui:text-amber-700',
    $alerts->contains(fn($a) => $a->isLow()) => 'ui:text-emerald-700',
    default => 'ui:text-slate-400',
  };

  $meta = trans_choice(':count subdomain|:count subdomains', $children->count(), ['count' => $children->count()])
    . ' · ' . trans_choice(':count monitored|:count monitored', $nbMonitored, ['count' => $nbMonitored]);

  // Same `selected` state as asset rows: the root asset panel, or the domain panel below
  $key = $root ? "asset-{$root['_asset']->id}" : "domain-{$tld}";
  $isSelected = "selected === '{$key}'";
@endphp

<li x-data="{ expanded: {{ $expanded ? 'true' : 'false' }} }" data-search-group="{{ $tld }}" class="ui:border-0 ui:border-t ui:border-solid ui:border-line">

  {{-- Summary line --}}
  <div role="button" tabindex="0" @click="expanded = !expanded; selected = '{{ $key }}'"
       @keydown.enter.prevent="expanded = !expanded; selected = '{{ $key }}'" :aria-expanded="expanded"
       :class="{{ $isSelected }} ? 'ui:bg-brand-50 ui:shadow-[inset_3px_0_0_var(--ui-color-brand-500)]' : 'ui:hover:bg-slate-50'"
       class="ui:flex ui:items-center ui:gap-3 ui:px-5 ui:py-2 ui:cursor-pointer ui:outline-none!">
    <x-phosphor-caret-right class="ui:size-4 ui:shrink-0 ui:text-slate-500 ui:transition-transform"
                            ::class="expanded && 'ui:rotate-90'"/>

    <span class="ui:flex ui:min-w-0 ui:flex-1 ui:flex-col ui:md:flex-row ui:md:items-baseline ui:md:gap-3">
      <span class="ui:truncate ui:text-sm ui:font-semibold ui:text-ink ui:md:max-w-[60%] ui:md:shrink-0">{{ $tld }}</span>
      <span class="ui:truncate ui:text-xs ui:text-slate-500">{{ $meta }}</span>
    </span>

    @if($nbMonitored > 0)
      <span class="ui:flex ui:w-32 ui:shrink-0 ui:items-baseline ui:justify-end ui:gap-1.5">
        <span class="ui:text-base ui:font-semibold ui:tabular-nums {{ $countColor }}">{{ $alerts->count() }}</span>
        <span class="ui:text-xs ui:text-slate-500">{{ trans_choice('vulnerability|vulnerabilities', $alerts->count()) }}</span>
      </span>
    @else
      <span class="ui:w-32 ui:shrink-0"></span>
    @endif

    <x-phosphor-caret-right class="ui:size-4 ui:shrink-0 ui:text-slate-400"/>
  </div>

  @if($root)
    <ul hidden>{!! $root['html'] !!}</ul>
  @else
    {{-- Domain panel: no root asset to carry the auto-monitoring flag, offer to add it --}}
    <template x-teleport="#asset-panel">
      <div x-show="{{ $isSelected }}" x-cloak class="ui:flex ui:flex-col ui:gap-4 ui:p-5">

        <div class="ui:flex ui:items-start ui:gap-3">
          <div class="ui:flex ui:min-w-0 ui:flex-1 ui:flex-col ui:gap-0.5">
            <span class="ui:break-all ui:text-base ui:font-semibold ui:text-ink">{{ $tld }}</span>
            <span class="ui:text-xs ui:text-slate-500">{{ $meta }}</span>
          </div>
          <x-ui.icon-button icon="x" :title="__('Close')" @click="selected = null"/>
        </div>

        <div class="ui:flex ui:flex-col ui:gap-3 ui:rounded-lg ui:border ui:border-solid ui:border-brand-200 ui:bg-brand-50 ui:p-4">
          <span class="ui:text-sm ui:font-semibold ui:text-ink">{{ __('Auto Monitor New Subdomains') }}</span>
          <span class="ui:text-xs ui:text-slate-600">
            {{ __('Add :domain to the inventory to automatically monitor the subdomains discovered.', ['domain' => $tld]) }}
          </span>
          <x-ui.button size="sm" class="ui:w-fit"
                       onclick="createAssetApiCall('{{ $tld }}', true, () => window.toaster.toastSuccess({{ json_encode(__('The monitoring started.')) }}))">
            {{ __('Add :domain', ['domain' => $tld]) }}
          </x-ui.button>
        </div>
      </div>
    </template>
  @endif

  {{-- Subdomains, indented under the domain --}}
  <ul x-show="expanded" x-collapse x-cloak class="ui:m-0 ui:list-none ui:p-0 ui:[&>li>[role=button]]:pl-12">
    @foreach($children as $row)
      {!! $row['html'] !!}
    @endforeach
  </ul>
</li>
