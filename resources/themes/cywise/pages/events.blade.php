<?php

use App\Http\Controllers\Iframes\EventsController;
use App\Http\Middleware\CheckPermissionsHttpRequest;
use App\Http\Middleware\LogHttpRequests;
use Illuminate\Http\Request;
use function Laravel\Folio\{middleware, name, render};

middleware([LogHttpRequests::class, 'auth', CheckPermissionsHttpRequest::class]);
name('events');
render(function (Request $request) {
  return app(EventsController::class)($request);
});
?>

<x-layouts.app>
  @php
    // Timeline items (date → time → events and IoC groups) flattened, most recent first.
    $rows = collect($items)
      ->flatMap(fn($times) => collect($times)->flatMap(fn($events) => $events))
      ->values();

    $level = request('level');
    $filters = request()->only(['server_id', 'rule_name']);
    $isFiltered = !empty(array_filter($filters)) || !empty($level);

    // KPI tiles double as level filters; clicking the active one clears it.
    $kpis = [
      ['level' => 'high', 'tone' => 'high', 'icon' => 'warning-octagon', 'value' => $nb_high, 'label' => __('High')],
      ['level' => 'medium', 'tone' => 'medium', 'icon' => 'warning-octagon', 'value' => $nb_medium, 'label' => __('Medium')],
      ['level' => 'low', 'tone' => 'low', 'icon' => 'warning-octagon', 'value' => $nb_low, 'label' => __('Low')],
      ['level' => 'suspect', 'tone' => 'neutral', 'icon' => 'question', 'value' => $nb_suspect, 'label' => __('Suspect')],
      ['level' => 'other', 'tone' => 'neutral', 'icon' => 'pulse', 'value' => $nb_events, 'label' => __('Other')],
    ];
  @endphp

  <div class="ui:mx-auto ui:flex ui:w-full ui:max-w-7xl ui:flex-col ui:gap-6 ui:px-4 ui:py-8 ui:lg:px-8">

    <x-ui.page-header :title="__('Events')"
                      :subtitle="__('Activity and indicators of compromise reported by the agent on your servers over the last 2 days.')"/>

    <div class="ui:grid ui:grid-cols-2 ui:gap-4 ui:sm:grid-cols-3 ui:xl:grid-cols-5">
      @foreach($kpis as $kpi)
        <x-ui.stat :tone="$kpi['tone']" :icon="$kpi['icon']" :value="$kpi['value']" :label="$kpi['label']"
                   :href="route('events', $level === $kpi['level'] ? $filters : array_merge($filters, ['level' => $kpi['level']]))"
                   :class="$level === $kpi['level'] ? 'ui:ring-2 ui:ring-brand-500 ui:border-transparent!' : ''"/>
      @endforeach
    </div>

    {{-- Filters --}}
    <form method="get" action="{{ route('events') }}"
          class="ui:m-0 ui:grid ui:items-end ui:gap-3 ui:rounded-xl ui:border ui:border-solid ui:border-line ui:bg-surface ui:p-4 ui:shadow-xs ui:sm:grid-cols-2 ui:lg:grid-cols-[1fr_2fr_auto]">
      <x-ui.field :label="__('Server')" for="server_id">
        <x-ui.select id="server_id" name="server_id">
          <option value="">{{ __('All servers') }}</option>
          @foreach($servers_with_active_events as $server)
            <option value="{{ $server->id }}" @selected((int)request('server_id') === $server->id)>{{ $server->name }} ({{ $server->nb_events ?? 0 }})</option>
          @endforeach
        </x-ui.select>
      </x-ui.field>
      <x-ui.field :label="__('Rule')" for="rule_name">
        <x-ui.select id="rule_name" name="rule_name">
          <option value="">{{ __('All rules') }}</option>
          @foreach($rules as $rule)
            @if(($rules_details[$rule->name]['nb_events'] ?? 0) > 0)
              <option value="{{ $rule->name }}" @selected(request('rule_name') === $rule->name)>{{ $rule->displayName() }} ({{ $rules_details[$rule->name]['nb_events'] ?? 0 }})</option>
            @endif
          @endforeach
        </x-ui.select>
      </x-ui.field>
      <div class="ui:flex ui:gap-2">
        <x-ui.button type="submit" icon="funnel">{{ __('Filter') }}</x-ui.button>
        @if($isFiltered)
          <x-ui.button variant="ghost" :href="route('events')">{{ __('Reset') }}</x-ui.button>
        @endif
      </div>
      @if($level)
        <input type="hidden" name="level" value="{{ $level }}">
      @endif
    </form>

    {{-- Selected rule. Filled server-side when filtered, then live by updateRuleDisplay() (iframes/_scripts) on select change. --}}
    @php
      $rule = $selected_rule ? ($rules_details[$selected_rule->name] ?? null) : null;
      $scoreLevel = fn(int $score) => match (true) { $score >= 75 => 'high', $score >= 50 => 'medium', $score >= 25 => 'info', default => 'neutral' };
    @endphp
    <x-ui.card id="selected-rule-card" style="{{ $rule ? '' : 'display: none;' }}">
      <div class="ui:flex ui:flex-col ui:gap-4">
        <div class="ui:flex ui:flex-wrap ui:items-start ui:justify-between ui:gap-3">
          <h2 id="rule-title" class="ui:m-0 ui:text-base ui:font-semibold ui:text-ink">
            @if($rule && $rule['can_edit'])
              <a href="{{ $rule['editor_url'] }}" class="ui:text-ink! ui:hover:text-brand-600!">{{ $rule['display_name'] }}</a>
            @elseif($rule)
              {{ $rule['display_name'] }}
            @endif
          </h2>
          <div id="rule-tactics" class="ui:flex ui:flex-wrap ui:gap-1.5">
            @foreach($rule['tactics'] ?? [] as $tactic)
              <x-ui.tag>{{ $tactic }}</x-ui.tag>
            @endforeach
          </div>
        </div>
        <p id="rule-description" class="ui:m-0 ui:text-sm ui:text-slate-600">{{ $rule['description'] ?? '' }}</p>
        <dl class="ui:m-0 ui:grid ui:gap-3 ui:text-sm ui:sm:grid-cols-3">
          <div>
            <dt class="ui:text-xs ui:font-semibold ui:uppercase ui:tracking-wide ui:text-slate-400">{{ __('Platform') }}</dt>
            <dd id="rule-platform-info" class="ui:m-0 ui:mt-1 ui:flex ui:gap-1.5">
              <x-ui.badge level="info" id="rule-platform">{{ $rule['platform'] ?? '' }}</x-ui.badge>
              <x-ui.badge id="rule-interval">{{ $rule['interval'] ?? '' }}</x-ui.badge>
            </dd>
          </div>
          <div>
            <dt class="ui:text-xs ui:font-semibold ui:uppercase ui:tracking-wide ui:text-slate-400">{{ __('IoC') }}</dt>
            <dd id="rule-ioc-info" class="ui:m-0 ui:mt-1 ui:flex ui:gap-1.5">
              @if($rule)
                <x-ui.badge :level="$rule['is_ioc'] ? 'high' : 'low'">{{ $rule['is_ioc'] ? __('yes') : __('no') }}</x-ui.badge>
                <x-ui.badge :level="$scoreLevel($rule['score'])">{{ $rule['score'] }} / 100</x-ui.badge>
              @endif
            </dd>
          </div>
          <div id="rule-mitre-row" style="{{ empty($rule['mitre'] ?? []) ? 'display: none;' : '' }}">
            <dt class="ui:text-xs ui:font-semibold ui:uppercase ui:tracking-wide ui:text-slate-400">{{ __('Mitre') }}</dt>
            <dd id="rule-mitre-links" class="ui:m-0 ui:mt-1 ui:flex ui:flex-wrap ui:gap-2">
              @foreach($rule['mitre'] ?? [] as $mitre)
                <a href="{{ $mitre['url'] }}" target="_blank">{{ $mitre['uid'] }}</a>
              @endforeach
            </dd>
          </div>
        </dl>
        <pre id="rule-query" class="ui:m-0 ui:overflow-auto ui:rounded-lg ui:bg-slate-50 ui:p-3 ui:text-xs ui:text-slate-700">{{ $rule['query'] ?? '' }}</pre>
      </div>
    </x-ui.card>

    {{-- List --}}
    <x-ui.card flush :title="trans_choice(':count event|:count events', $rows->count(), ['count' => $rows->count()])"
               :subtitle="$rows->isEmpty() ? null : __('Repeated indicators are grouped. Click a row to see the raw data.')">
      @if($rows->isEmpty())
        <x-ui.empty>
          {{ $isFiltered ? __('No event matches these filters.') : __('Good job! No indicators of compromise (IoCs) to investigate.') }}
        </x-ui.empty>
      @else
        <ul class="ui:m-0 ui:list-none ui:p-0">
          @foreach($rows as $row)
            {!! $row['html'] !!}
          @endforeach
        </ul>
      @endif
    </x-ui.card>
  </div>

  @include('theme::iframes._scripts')
</x-layouts.app>
