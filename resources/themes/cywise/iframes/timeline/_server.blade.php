{{--
  Server row (agent installed with the setup script). Same condensed line as an asset row (iframes/timeline/_asset):
  clicking it shows the details in the page side panel.

    (server) name  Agent · 203.0.113.20 · added by …                    >
--}}
@php
  // Monitoring sources, shown in the meta line instead of badges
  $sources = array_filter([
    $server->isMonitored() ? __('Scanner') : null,
    $server->isReady() ? __('Agent') : null,
  ]);

  $meta = implode(' · ', array_filter([
    ...$sources,
    $server->ip() ?? $server->ipv6(),
    __('Added by :user on :date', ['user' => $server->user?->name ?? '-', 'date' => $date]),
  ]));

  // Prefixed key: asset and server ids share the same `selected` state (pages/assets)
  $key = "server-{$server->id}";
  $isSelected = "selected === '{$key}'";
@endphp

<li id="servid-{{ $server->id }}" data-search class="ui:border-0 ui:border-t ui:border-solid ui:border-line">

  {{-- Summary line --}}
  <div role="button" tabindex="0" @click="selected = '{{ $key }}'"
       @keydown.enter.prevent="selected = '{{ $key }}'" :aria-pressed="{{ $isSelected }}"
       :class="{{ $isSelected }} ? 'ui:bg-brand-50 ui:shadow-[inset_3px_0_0_var(--ui-color-brand-500)]' : 'ui:hover:bg-slate-50'"
       class="ui:flex ui:items-center ui:gap-3 ui:px-5 ui:py-2 ui:cursor-pointer ui:outline-none!">
    <x-phosphor-hard-drives class="ui:size-4 ui:shrink-0 ui:text-slate-400"/>

    <span class="ui:flex ui:min-w-0 ui:flex-1 ui:flex-col ui:md:flex-row ui:md:items-baseline ui:md:gap-3">
      <span class="ui:truncate ui:text-sm ui:font-semibold ui:text-ink ui:md:max-w-[60%] ui:md:shrink-0">{{ $server->name }}</span>
      <span class="ui:truncate ui:text-xs ui:text-slate-500">{{ $meta }}</span>
    </span>

    <x-phosphor-caret-right class="ui:size-4 ui:shrink-0 ui:text-slate-400"/>
  </div>

  {{-- Details (rendered in the side panel) --}}
  <template x-teleport="#asset-panel">
    <div x-show="{{ $isSelected }}" x-cloak class="ui:flex ui:flex-col ui:gap-4 ui:p-5">

      <div class="ui:flex ui:items-start ui:gap-3">
        <div class="ui:flex ui:min-w-0 ui:flex-1 ui:flex-col ui:gap-0.5">
          <span class="ui:break-all ui:text-base ui:font-semibold ui:text-ink">{{ $server->name }}</span>
          <span class="ui:text-xs ui:text-slate-500">{{ $meta }}</span>
        </div>
        <x-ui.icon-button icon="x" :title="__('Close')" @click="selected = null"/>
      </div>

      <a href="{{ route('events', ['server_id' => $server->id]) }}"
         class="ui:flex ui:w-fit ui:items-center ui:gap-1.5 ui:text-sm ui:text-slate-600! ui:no-underline! ui:hover:text-ink!">
        {{ __('Events') }}
        <x-phosphor-caret-right class="ui:size-3.5 ui:text-slate-400"/>
      </a>
    </div>
  </template>
</li>
