@extends('theme::iframes.app')

@section('content')
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
  <div class="card-body p-0">
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
          <a href="mailto:{{ $trace->user_email }}" target="_blank">
            {{ $trace->user_name }}
          </a>
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