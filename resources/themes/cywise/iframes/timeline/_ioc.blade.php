{{--
  IoC row: one row per group of identical IoCs (same server, same rule, same day).
  Details (on click): raw osquery columns of the first and, when grouped, the last occurrence.
  Auto-opens when targeted by the URL hash (e.g. dashboard link "events#eid-42").
--}}
@php
  $first = $ioc['first']['ioc'];
  $last = $ioc['last']['ioc'];
  $count = $ioc['in_between'];

  $levelKey = match (true) {
    $first->score >= 75 => 'high',
    $first->score >= 50 => 'medium',
    $first->score >= 25 => 'low',
    default => 'neutral',
  };
  $levelLabel = match ($levelKey) {
    'high' => __('Severity high'),
    'medium' => __('Severity medium'),
    'low' => __('Severity low'),
    default => __('Suspect'),
  };
@endphp

<li id="eid-{{ $first->id }}" data-severity="{{ $levelKey }}" x-data="{ open: false }"
    x-init="if (['#eid-{{ $first->id }}', '#eid-{{ $last->id }}'].includes(location.hash)) { open = true; $nextTick(() => $el.scrollIntoView({ block: 'center' })) }"
    class="ui:border-0 ui:border-t ui:border-solid ui:border-line">

  <div class="ui:flex ui:items-center ui:gap-4 ui:px-5 ui:py-3 ui:hover:bg-slate-50">
    <button type="button" @click="open = !open" :aria-expanded="open"
            class="ui:flex ui:min-w-0 ui:flex-1 ui:items-center ui:gap-4 ui:border-0 ui:bg-transparent ui:p-0 ui:text-left ui:cursor-pointer">
      <span class="ui:flex ui:min-w-0 ui:flex-1 ui:flex-col">
        <span class="ui:flex ui:items-center ui:gap-2">
          <span class="ui:truncate ui:text-sm ui:font-medium ui:text-ink">{{ $first->comments }}</span>
          @if($count > 1)
            <x-ui.badge class="ui:shrink-0">× {{ $count }}</x-ui.badge>
          @endif
        </span>
        <span class="ui:truncate ui:text-xs ui:text-slate-500">{{ $first->server_name }} ({{ $first->server_ip_address }})</span>
      </span>
      <span class="ui:w-16 ui:shrink-0 ui:text-right ui:text-xs ui:font-medium ui:text-slate-500">{{ $levelLabel }}</span>
      <span class="ui:hidden ui:w-28 ui:shrink-0 ui:text-right ui:text-xs ui:text-slate-400 ui:md:block">{{ $ioc['first']['date'] }} {{ $ioc['first']['time'] }}</span>
      <x-phosphor-caret-down class="ui:size-4 ui:shrink-0 ui:text-slate-400 ui:transition-transform"
                             ::class="open && 'ui:rotate-180'"/>
    </button>
    <x-ui.icon-button icon="eye-slash" :title="__('Hide events like this for this server in the timeline.')" onclick="dismissEvent('{{ $first->id }}')"/>
  </div>

  <div x-show="open" x-collapse x-cloak>
    <div class="ui:grid ui:gap-3 ui:px-5 ui:pb-5 {{ $count > 1 ? 'ui:lg:grid-cols-2' : '' }}">
      <div class="ui:min-w-0">
        @if($count > 1)
          <div class="ui:mb-1 ui:text-xs ui:font-semibold ui:uppercase ui:tracking-wide ui:text-slate-400">{{ __('First occurrence') }} · {{ $ioc['first']['time'] }}</div>
        @endif
        <pre class="ui:m-0 ui:max-h-72 ui:overflow-auto ui:rounded-lg ui:bg-slate-50 ui:p-3 ui:text-xs ui:text-slate-700">{{ json_encode($first->columns, JSON_PRETTY_PRINT) }}</pre>
      </div>
      @if($count > 1)
        <div class="ui:min-w-0">
          <div class="ui:mb-1 ui:text-xs ui:font-semibold ui:uppercase ui:tracking-wide ui:text-slate-400">{{ __('Last occurrence') }} · {{ $ioc['last']['time'] }}</div>
          <pre class="ui:m-0 ui:max-h-72 ui:overflow-auto ui:rounded-lg ui:bg-slate-50 ui:p-3 ui:text-xs ui:text-slate-700">{{ json_encode($last->columns, JSON_PRETTY_PRINT) }}</pre>
        </div>
      @endif
    </div>
  </div>
</li>
