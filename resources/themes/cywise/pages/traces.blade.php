<?php

use App\Http\Controllers\Iframes\TracesController;
use App\Http\Middleware\CheckPermissionsHttpRequest;
use App\Http\Middleware\LogHttpRequests;
use Illuminate\Http\Request;
use function Laravel\Folio\{middleware, name, render};

middleware([LogHttpRequests::class, 'auth', CheckPermissionsHttpRequest::class]);
name('traces');
render(function (Request $request) {
  return app(TracesController::class)($request);
});
?>

<x-layouts.app>
  <div class="ui:mx-auto ui:flex ui:w-full ui:max-w-7xl ui:flex-col ui:gap-6 ui:px-4 ui:py-8 ui:lg:px-8">

    <x-ui.page-header :title="__('Traces')" :subtitle="__('Latest HTTP and JSON-RPC calls, with their duration and status.')"/>

    {{-- Successes vs. failures per 5 minutes slot, stacked columns (charts.css) --}}
    @if($traces->isNotEmpty())
      <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/charts.css/dist/charts.min.css">
      @php
        $groups = $traces->sortBy('created_at')->groupBy(fn ($trace) =>
          "[{$trace->created_at->floorMinute(5)->format('Y-m-d H:i')}, {$trace->created_at->ceilMinute(5)->format('Y-m-d H:i')}]")
          ->map(function ($group) {
            return [
              'success' => $group->where('failed', false)->count(),
              'failure' => $group->where('failed', true)->count(),
            ];
          });
        $maxCount = $groups->map(fn($group) => $group['success'] + $group['failure'])->max();
      @endphp
      <x-ui.card :title="__('Activity')">
        <div class="ui:overflow-hidden">
          <table class="charts-css column hide-data show-primary-axis show-4-secondary-axes data-spacing-1 multiple stacked">
            <thead>
            <tr>
              <th scope="col">{{ __('Timestamp') }}</th>
              <th scope="col">{{ __('Successes') }}</th>
              <th scope="col">{{ __('Failures') }}</th>
            </tr>
            </thead>
            <tbody style="height: 200px">
            @foreach($groups as $timestamp => $counts)
              <tr>
                <th scope="row">{{ $timestamp }}</th>
                <td style="--size: {{ $counts['success'] / $maxCount }}; --color: color-mix(in srgb, var(--ui-color-low) 35%, transparent); border: {{ $counts['success'] === 0 ? 'none' : '1px solid var(--ui-color-low)' }}; border-bottom: none">
                  <span class="data">{{ $counts['success'] }}</span>
                  <span class="tooltip">{{ __('Successes') }}: {{ $counts['success'] }}<br>{{ $timestamp }}</span>
                </td>
                <td style="--size: {{ $counts['failure'] / $maxCount }}; --color: color-mix(in srgb, var(--ui-color-high) 35%, transparent); border: {{ $counts['failure'] === 0 ? 'none' : '1px solid var(--ui-color-high)' }}; border-bottom: none">
                  <span class="data">{{ $counts['failure'] }}</span>
                  <span class="tooltip">{{ __('Failures') }}: {{ $counts['failure'] }}<br>{{ $timestamp }}</span>
                </td>
              </tr>
            @endforeach
            </tbody>
          </table>
        </div>
      </x-ui.card>
    @endif

    <x-ui.card flush>
      @if($traces->isEmpty())
        <x-ui.empty icon="list-dashes">{{ __('None.') }}</x-ui.empty>
      @else
        <x-ui.table>
          <thead>
          <tr>
            <th>{{ __('Timestamp') }}</th>
            <th>{{ __('User') }}</th>
            <th>{{ __('Verb') }}</th>
            <th>{{ __('Endpoint') }}</th>
            <th>{{ __('Procedure') }}</th>
            <th>{{ __('Method') }}</th>
            <th class="ui:text-right!">{{ __('Duration in ms') }}</th>
            <th>{{ __('Status') }}</th>
          </tr>
          </thead>
          <tbody>
          @foreach($traces as $trace)
            <tr>
              <td class="ui:whitespace-nowrap ui:font-medium ui:tabular-nums">
                {{ $trace->created_at->format('Y-m-d H:i:s') }}
              </td>
              <td>
                @if(isset($trace->user_email))
                  <a href="mailto:{{ $trace->user_email }}" target="_blank" class="ui:text-brand-600! ui:no-underline! ui:hover:underline!">
                    {{ $trace->user_name }}
                  </a>
                @else
                  -
                @endif
              </td>
              <td><x-ui.badge>{{ $trace->verb }}</x-ui.badge></td>
              <td class="ui:font-mono ui:text-xs ui:break-all">{{ $trace->endpoint }}</td>
              <td class="ui:font-mono ui:text-xs">{{ $trace->procedure ?? '-' }}</td>
              <td class="ui:font-mono ui:text-xs">{{ $trace->method ?? '-' }}</td>
              <td class="ui:text-right ui:tabular-nums">{{ $trace->duration_in_ms }}</td>
              <td>
                @if($trace->failed)
                  <x-ui.badge level="high">{{ __('failure') }}</x-ui.badge>
                @else
                  <x-ui.badge level="low">{{ __('success') }}</x-ui.badge>
                @endif
              </td>
            </tr>
          @endforeach
          </tbody>
        </x-ui.table>
      @endif
    </x-ui.card>
  </div>
</x-layouts.app>

