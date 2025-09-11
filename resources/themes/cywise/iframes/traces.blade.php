@extends('theme::iframes.app')

@section('content')
@if($traces->isNotEmpty())
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/charts.css/dist/charts.min.css">
<div class="card mt-3 mb-3">
  <div class="card-body">
    <div class="card-text">
      <table
        class="charts-css column hide-data show-primary-axis show-4-secondary-axes data-spacing-1 multiple stacked">
        <thead>
        <tr>
          <th scope="col">{{ __('Timestamp') }}</th>
          <th scope="col">{{ __('Successes') }}</th>
          <th scope="col">{{ __('Failures') }}</th>
        </tr>
        </thead>
        @php
        $groups = $traces->sortBy('created_at')->groupBy(fn ($trace) =>
        "[{$trace->created_at->floorMinute(5)->format('Y-m-d H:i')}, {$trace->created_at->ceilMinute(5)->format('Y-m-d
        H:i')}]")
        ->map(function ($group) {
        return [
        'success' => $group->where('failed', false)->count(),
        'failure' => $group->where('failed', true)->count(),
        ];
        });
        $maxCount = $groups->map(fn($group) => $group['success'] + $group['failure'])->max();
        @endphp
        <tbody style="height: 200px">
        @foreach($groups as $timestamp => $counts)
        <tr>
          <th scope="row">{{ $timestamp }}</th>
          <td
            style="--size: {{ $counts['success'] / $maxCount }}; --color: var(--ds-background-success); border: {{ $counts['success'] === 0 ? 'none' : '1px solid var(--ds-text-success)' }}; border-bottom: none">
            <span class="data">{{ $counts['success'] }}</span>
            <span class="tooltip text-left">
              {{ __('Successes') }}: {{ $counts['success'] }}<br>{{ $timestamp }}
            </span>
          </td>
          <td
            style="--size: {{ $counts['failure'] / $maxCount }}; --color: var(--ds-background-danger); border: {{ $counts['failure'] === 0 ? 'none' : '1px solid var(--ds-text-danger)' }}; border-bottom: none">
            <span class="data">{{ $counts['failure'] }}</span>
            <span class="tooltip">
              {{ __('Failures') }}: {{ $counts['failure'] }}<br>{{ $timestamp }}
            </span>
          </td>
        </tr>
        @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>
@endif
<div class="card mt-3 mb-3">
  @if($traces->isEmpty())
  <div class="card-body">
    <div class="row">
      <div class="col">
        {{ __('None.') }}
      </div>
    </div>
  </div>
  @else
  <div class="card-body p-0 mt-3">
    <table class="table table-hover mb-0">
      <thead>
      <tr>
        <th>{{ __('Timestamp') }}</th>
        <th>{{ __('User') }}</th>
        <th>{{ __('Verb') }}</th>
        <th>{{ __('Endpoint') }}</th>
        <th>{{ __('Procedure') }}</th>
        <th>{{ __('Method') }}</th>
        <th style="text-align: right">{{ __('Duration in ms') }}</th>
        <th>{{ __('Status') }}</th>
      </tr>
      </thead>
      <tbody>
      @foreach($traces as $trace)
      <tr>
        <td>
          <span class="font-lg mb-3 fw-bold">
            {{ $trace->created_at->format('Y-m-d H:i:s') }}
          </span>
        </td>
        <td>
          @if(isset($trace->user_email))
          <a href="mailto:{{ $trace->user_email }}" target="_blank">
            {{ $trace->user_name }}
          </a>
          @else
          -
          @endif
        </td>
        <td>
          {{ $trace->verb }}
        </td>
        <td>
          {{ $trace->endpoint }}
        </td>
        <td>
          {{ $trace->procedure ?? '-' }}
        </td>
        <td>
          {{ $trace->method ?? '-' }}
        </td>
        <td style="text-align: right">
          {{ $trace->duration_in_ms }}
        </td>
        <td>
          @if($trace->failed)
          <span class='lozenge error'>{{ __('failure') }}</span>
          @else
          <span class='lozenge success'>{{ __('success') }}</span>
          @endif
        </td>
      </tr>
      @endforeach
      </tbody>
    </table>
  </div>
  @endif
</div>
@endsection