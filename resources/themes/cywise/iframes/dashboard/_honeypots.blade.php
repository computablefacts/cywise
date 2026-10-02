{{-- Honeypots: attacks per day (charts.css stacked columns) + 5 most recent attacks. --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/charts.css/dist/charts.min.css">

<div class="ui:grid ui:gap-6 ui:md:grid-cols-2 ui:xl:grid-cols-3">
  @foreach($honeypots as $honeypot)
    <x-ui.card :title="$honeypot['name']" :subtitle="__('Honeypot') . ' ' . $honeypot['type']">
      <div class="ui:flex ui:flex-col ui:gap-4">
        @if(\Illuminate\Support\Str::endsWith($honeypot['name'], '.cywise.io'))
          <p class="ui:m-0 ui:text-sm ui:text-slate-500">{{ __('Would you like to redirect one of your domains to this honeypot? Contact support!') }}</p>
        @endif

        @if(empty($honeypot['counts']) || ($honeypot['max'] ?? 0) <= 0)
          <p class="ui:m-0 ui:text-sm ui:text-slate-500">{{ __('No recent events.') }}</p>
        @else
          <table class="charts-css column hide-data show-primary-axis show-3-secondary-axes data-spacing-1 multiple stacked ui:h-32">
            <thead>
              <tr>
                <th scope="col">{{ __('Date') }}</th>
                <th scope="col">{{ __('Human or Targeted') }}</th>
                <th scope="col">{{ __('Bots') }}</th>
              </tr>
            </thead>
            <tbody>
              @foreach($honeypot['counts'] as $count)
                <tr>
                  <th scope="row">{{ $count['date'] }}</th>
                  <td style="--size: calc({{ $count['human_or_targeted'] }} / {{ $honeypot['max'] }}); --color: var(--ui-color-brand-500);">
                    <span class="data">{{ $count['human_or_targeted'] }}</span>
                    <span class="tooltip">{{ __('Human or Targeted') }}: {{ $count['human_or_targeted'] }}<br>{{ $count['date'] }}</span>
                  </td>
                  <td style="--size: calc({{ $count['not_human_or_targeted'] }} / {{ $honeypot['max'] }}); --color: var(--ui-color-slate-300);">
                    <span class="data">{{ $count['not_human_or_targeted'] }}</span>
                    <span class="tooltip">{{ __('Bots') }}: {{ $count['not_human_or_targeted'] }}<br>{{ $count['date'] }}</span>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
          <div class="ui:flex ui:gap-4 ui:text-xs ui:text-slate-500">
            <span class="ui:flex ui:items-center ui:gap-1.5"><span class="ui:size-2 ui:rounded-full ui:bg-brand-500"></span>{{ __('Human or Targeted') }}</span>
            <span class="ui:flex ui:items-center ui:gap-1.5"><span class="ui:size-2 ui:rounded-full ui:bg-slate-300"></span>{{ __('Bots') }}</span>
          </div>
        @endif

        @if(!empty($most_recent_honeypot_events[$honeypot['name']]['events']))
          <div>
            <div class="ui:mb-1 ui:text-xs ui:font-semibold ui:uppercase ui:tracking-wide ui:text-slate-500">{{ __('Most recent attacks') }}</div>
            <ul class="ui:m-0 ui:list-none ui:p-0">
              @foreach($most_recent_honeypot_events[$honeypot['name']]['events'] as $event)
                <li class="ui:flex ui:items-center ui:gap-3 ui:border-0 ui:border-t ui:border-solid ui:border-line ui:py-2 ui:text-sm" title="{{ $event['event_details'] }}">
                  @if($event['attacker_name'] !== '-')
                    <x-phosphor-user class="ui:size-4 ui:shrink-0 ui:text-brand-500" aria-label="{{ __('Human or Targeted') }}"/>
                  @else
                    <x-phosphor-robot class="ui:size-4 ui:shrink-0 ui:text-slate-400" aria-label="{{ __('Bots') }}"/>
                  @endif
                  <span class="ui:truncate ui:text-ink">{{ $event['event_type'] }}</span>
                  <span class="ui:ml-auto ui:shrink-0 ui:text-xs ui:text-slate-500 ui:tabular-nums">{{ $event['timestamp'] }}</span>
                </li>
              @endforeach
            </ul>
          </div>
        @endif
      </div>
    </x-ui.card>
  @endforeach
</div>
