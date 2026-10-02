{{--
  Dashboard once something is monitored. Ordered by urgency:

    With a server agent              Without
    ┌───────┬───────┬───────┐        ┌──────┬──────┬──────┬──────┐
    │assets │ high  │medium │        │assets│ high │medium│ low  │
    ├───────┼───────┼───────┤        ├──────┴──────┴─┬────┴──────┤
    │ low   │events │ Buddy │        │ top 5 to fix  │ install   │
    ├───────┴───────┼───────┤        │               │ agent     │
    │ top 5 to fix  │ top 5 │        │               ├───────────┤
    │               │events │        │               │ Buddy     │
    ├───────────────┴───────┤        └───────────────┴───────────┘
    (then, in both cases)
    ┌─────────────────────────────┐
    │ SOC reports, leaks          │   shown only when not empty
    ├─────────────────────────────┤
    │ ▸ add a server   ▸ bots     │   setup, collapsed
    └─────────────────────────────┘
--}}
@php
  $firstName = Str::before(trim(Auth::user()->name), ' ');
  $nbIocs = $nb_iocs_high + $nb_iocs_medium;

  $alertLevel = fn($alert) => match (true) {
    $alert->isCritical() => 'critical',
    $alert->isHigh() => 'high',
    $alert->isMedium() => 'medium',
    $alert->isLow() => 'low',
    default => 'info',
  };
  $eventLevel = fn($event) => match (true) {
    $event->score >= 75 => 'high',
    $event->score >= 50 => 'medium',
    $event->score >= 25 => 'low',
    default => 'info',
  };
  $levelLabel = fn(string $level) => match ($level) {
    'critical' => __('Critical'),
    'high' => __('Severity high'),
    'medium' => __('Severity medium'),
    'low' => __('Severity low'),
    default => __('Severity info'),
  };
@endphp

<div class="ui:flex ui:flex-col ui:gap-6">

  <x-ui.page-header :title="__('Hello :name', ['name' => $firstName])"
                    :subtitle="__('Here is the security status of your perimeter.')"/>

  {{--
    Tiles. With a server: 6 tiles (3 × 2), events + CyberBuddy included.
    Without: the 4 KPIs only; the agent nudge and CyberBuddy sit next to the top 5 below.
  --}}
  <div class="ui:grid ui:auto-rows-fr ui:grid-cols-1 ui:gap-4 ui:sm:grid-cols-2 {{ $has_servers ? 'ui:lg:grid-cols-3' : 'ui:xl:grid-cols-4' }}">
    <x-ui.stat tone="info" icon="globe" :value="$nb_monitored" :label="__('Monitored assets')"
               :hint="$nb_monitorable > 0 ? __(':n not monitored yet', ['n' => $nb_monitorable]) : null"
               :href="route('assets')"/>
    <x-ui.stat tone="high" icon="warning-octagon" :value="$nb_vulns_high" :label="__('High vulnerabilities')"
               :href="route('vulnerabilities', ['level' => 'high'])"/>
    <x-ui.stat tone="medium" icon="warning-octagon" :value="$nb_vulns_medium" :label="__('Medium vulnerabilities')"
               :href="route('vulnerabilities', ['level' => 'medium'])"/>
    <x-ui.stat tone="low" icon="warning-octagon" :value="$nb_vulns_low" :label="__('Low vulnerabilities')"
               :href="route('vulnerabilities', ['level' => 'low'])"/>

    @if($has_servers)
      <x-ui.stat :tone="$nbIocs > 0 ? 'high' : 'low'" icon="pulse" :value="$nbIocs" :label="__('Events to investigate')"
                 :hint="__(':n low severity', ['n' => $nb_iocs_low])"
                 :href="route('events')"/>

      @include('theme::iframes.dashboard._cyberbuddy-tile')
    @endif
  </div>

  {{-- What to do now: top 5 vulnerabilities + right column (events, or agent nudge and CyberBuddy) --}}
  {{-- Equal heights when both lists have rows; an empty list keeps its natural (small) height --}}
  @php
    $alignHeights = $has_servers && count($todo) > 0 && count($investigate) > 0;
  @endphp
  <div class="ui:grid ui:gap-6 ui:lg:grid-cols-3 {{ $alignHeights ? 'ui:items-stretch' : 'ui:items-start' }}">

    <x-ui.card class="ui:lg:col-span-2" flush
               :title="__('Top 5 vulnerabilities to fix')"
               :href="route('vulnerabilities')">
      @if(count($todo) <= 0)
        <x-ui.empty>{{ __('Good job! No vulnerabilities to fix.') }}</x-ui.empty>
      @else
        <ul class="ui:m-0 ui:list-none ui:p-0">
          @foreach($todo as $item)
            @php
              $level = $alertLevel($item);
            @endphp
            <li data-severity="{{ $level }}" class="ui:border-0 ui:border-t ui:border-solid ui:border-line">
              <a href="{{ route('vulnerabilities') }}#vid-{{ $item->id }}"
                 class="ui:flex ui:items-center ui:gap-4 ui:px-5 ui:py-3 ui:no-underline! ui:hover:bg-slate-50">
                <span class="ui:flex ui:min-w-0 ui:flex-1 ui:flex-col">
                  <span class="ui:truncate ui:text-sm ui:font-medium ui:text-ink!">
                    @if(empty($item->cve_id))
                      {{ $item->title }}
                    @else
                      {{ $item->cve_id }} · {{ $item->title }}
                    @endif
                  </span>
                  <span class="ui:truncate ui:text-xs ui:text-slate-500!">{{ $item->asset()->asset }}</span>
                </span>
                <span class="ui:shrink-0 ui:text-xs ui:font-medium ui:text-slate-500!">{{ $levelLabel($level) }}</span>
              </a>
            </li>
          @endforeach
        </ul>
      @endif
    </x-ui.card>

    @if($has_servers)
      <x-ui.card flush :title="__('Top 5 events to investigate')" :href="route('events')">
        @if(count($investigate) <= 0)
          <x-ui.empty>{{ __('Good job! No indicators of compromise (IoCs) to investigate.') }}</x-ui.empty>
        @else
          <ul class="ui:m-0 ui:list-none ui:p-0">
            @foreach($investigate as $item)
              @php
                $level = $eventLevel($item);
              @endphp
              <li data-severity="{{ $level }}" class="ui:border-0 ui:border-t ui:border-solid ui:border-line">
                <a href="{{ route('events') }}#eid-{{ $item->id }}"
                   class="ui:flex ui:items-center ui:gap-3 ui:px-5 ui:py-3 ui:no-underline! ui:hover:bg-slate-50">
                  <span class="ui:flex ui:min-w-0 ui:flex-1 ui:flex-col">
                    <span class="ui:truncate ui:text-sm ui:font-medium ui:text-ink!">{{ $item->comments }}</span>
                    <span class="ui:truncate ui:text-xs ui:text-slate-500!">{{ $item->server_name }}</span>
                  </span>
                  <span class="ui:shrink-0 ui:text-xs ui:font-medium ui:text-slate-500!">{{ $levelLabel($level) }}</span>
                </a>
              </li>
            @endforeach
          </ul>
        @endif
      </x-ui.card>
    @else
      <div class="ui:flex ui:flex-col ui:gap-6">
        {{-- Nudge: events need the server agent. Opens the setup section below. --}}
        <x-ui.card>
          <div class="ui:flex ui:flex-col ui:items-start ui:gap-3">
            <span class="ui:flex ui:size-10 ui:items-center ui:justify-center ui:rounded-xl ui:bg-ink ui:text-white">
              <x-phosphor-hard-drives-bold class="ui:size-5"/>
            </span>
            <h2 class="ui:m-0 ui:text-base ui:font-semibold ui:text-ink">{{ __('Protect your servers too') }}</h2>
            <p class="ui:m-0 ui:text-sm ui:text-slate-500">{{ __('Install the Cywise agent to detect suspicious behaviors and audit the server configuration.') }}</p>
            <x-ui.button variant="secondary" size="sm" icon="arrow-down"
                         onclick="const d = document.getElementById('server-install'); d.open = true; d.scrollIntoView({ behavior: 'smooth' });">
              {{ __('Install the agent') }}
            </x-ui.button>
          </div>
        </x-ui.card>

        @include('theme::iframes.dashboard._cyberbuddy-tile')
      </div>
    @endif
  </div>

  {{-- SOC operator reports --}}
  @if(count($reports) > 0)
    <x-ui.card flush :title="__('SOC operator reports')">
      <div class="ui:overflow-x-auto">
        <table class="ui:w-full ui:border-collapse ui:text-sm">
          <thead>
            <tr class="ui:bg-slate-50 ui:text-left ui:text-xs ui:uppercase ui:tracking-wide ui:text-slate-500">
              <th class="ui:px-5 ui:py-2 ui:font-medium ui:w-32">{{ __('Report Date') }}</th>
              <th class="ui:px-5 ui:py-2 ui:font-medium">{{ __('Title') }}</th>
              <th class="ui:px-5 ui:py-2 ui:font-medium ui:text-right ui:w-28">{{ __('Events') }}</th>
              <th class="ui:px-5 ui:py-2 ui:font-medium ui:text-right ui:w-32">{{ __('Activity') }}</th>
            </tr>
          </thead>
          <tbody>
            @foreach($reports as $report)
              @php
                $eventsCount = '-';
                if (preg_match('/Evènements\s*\((\d+)\)/u', $report->body, $matches)) {
                    $eventsCount = $matches[1];
                }
                $activity = 'UNKNOWN';
                if (preg_match('/Activité\s*(?:<[^>]+>)*\s*:\s*(?:<[^>]+>)*\s*([^\s<]+)/u', $report->body, $matches)) {
                    $activity = match(mb_strtolower(trim($matches[1]))) {
                        'normale' => 'NORMAL',
                        'suspecte' => 'SUSPICIOUS',
                        'anormale' => 'ANORMAL',
                        default => 'UNKNOWN',
                    };
                }
                $activityLevel = match($activity) {
                    'NORMAL' => 'low',
                    'SUSPICIOUS' => 'medium',
                    'ANORMAL' => 'high',
                    default => 'neutral',
                };
              @endphp
              <tr class="ui:border-0 ui:border-t ui:border-solid ui:border-line">
                <td class="ui:px-5 ui:py-3 ui:text-slate-500">{{ $report->created_at?->format('Y-m-d') }}</td>
                <td class="ui:px-5 ui:py-3">
                  <a href="{{ $report->link() }}" target="_blank" class="ui:font-medium ui:text-ink! ui:hover:text-brand-600!">{{ $report->title }}</a>
                </td>
                <td class="ui:px-5 ui:py-3 ui:text-right ui:tabular-nums">{{ $eventsCount }}</td>
                <td class="ui:px-5 ui:py-3 ui:text-right">
                  <x-ui.badge :level="$activityLevel">{{ __($activity) }}</x-ui.badge>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </x-ui.card>
  @endif

  {{-- Leaks --}}
  @if(count($leaks) > 0)
    <x-ui.card flush :title="__('Latest data leaks')" :href="route('leaks')">
      <div class="ui:overflow-x-auto">
        <table class="ui:w-full ui:border-collapse ui:text-sm">
          <thead>
            <tr class="ui:bg-slate-50 ui:text-left ui:text-xs ui:uppercase ui:tracking-wide ui:text-slate-500">
              <th class="ui:w-px ui:px-5 ui:py-2 ui:font-medium ui:whitespace-nowrap">{{ __('Date') }}</th>
              <th class="ui:px-5 ui:py-2 ui:font-medium">{{ __('Email') }}</th>
              <th class="ui:px-5 ui:py-2 ui:font-medium">{{ __('Website') }}</th>
              <th class="ui:px-5 ui:py-2 ui:font-medium">{{ __('Password') }}</th>
              <th class="ui:px-5 ui:py-2 ui:font-medium">{{ __('Source') }}</th>
            </tr>
          </thead>
          <tbody>
            @foreach($leaks as $l)
              <tr class="ui:border-0 ui:border-t ui:border-solid ui:border-line">
                <td class="ui:px-5 ui:py-3 ui:whitespace-nowrap ui:text-slate-500">{{ empty($l->leak_date) ? '-' : $l->leak_date?->format('Y-m-d') }}</td>
                <td class="ui:px-5 ui:py-3 ui:text-ink">{{ $l->email }}</td>
                <td class="ui:px-5 ui:py-3 ui:max-w-64 ui:truncate ui:text-slate-600">{{ empty($l->website) ? '-' : $l->website }}</td>
                <td class="ui:px-5 ui:py-3 ui:font-mono ui:text-xs ui:text-slate-600">{{ empty($l->password) ? '-' : $l->password }}</td>
                <td class="ui:px-5 ui:py-3">
                  <x-ui.badge>{{ $l->leak_type ?? (empty($l->website) ? __('leak') : __('infostealer log')) }}</x-ui.badge>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </x-ui.card>
  @endif

  {{-- Setup, collapsed: rarely needed once onboarded --}}
  <div class="ui:flex ui:flex-col ui:gap-3">
    <h2 class="ui:m-0 ui:text-xs ui:font-semibold ui:uppercase ui:tracking-wider ui:text-slate-400">{{ __('Configuration') }}</h2>
    <x-ui.disclosure id="server-install" icon="hard-drives"
                     :title="__('Would you like to protect a new server?')"
                     :subtitle="__('Install the Cywise agent on Linux or Windows.')">
      @include('theme::iframes.dashboard._server-install')
    </x-ui.disclosure>
    <x-ui.disclosure icon="chat-circle-dots"
                     :title="__('Interact with Cywise through a bot')"
                     :subtitle="__('Telegram or WhatsApp, answered by :name.', ['name' => tenant_custom_text('CyberBuddy')])">
      @include('theme::iframes.dashboard._bots')
    </x-ui.disclosure>
  </div>
</div>
