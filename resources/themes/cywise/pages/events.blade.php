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
  @include('theme::iframes._styles')
  <div class="container-fluid">
    @include('theme::iframes.timeline._event-counters')
    <div class="row mt-3 mb-1">
      <div class="col">
        <div class="card">
          <div class="card-body p-3">
            <form method="get" action="{{ route('events') }}" class="row g-2 align-items-end">
              <div class="col-sm-8">
                <label for="rule_name" class="form-label">
                  {{ __('Rule') }}
                </label>
                <select id="rule_name" name="rule_name" class="form-select">
                  <option value="">{{ __('All rules') }}</option>
                  @foreach($rules as $rule)
                  <option value="{{ $rule->name }}" {{ request(
                  'rule_name') === $rule->name ? 'selected' : '' }}>
                  {{ $rule->displayName() }}
                  </option>
                  @endforeach
                </select>
              </div>
              <div class="col-sm-2">
                <label class="form-label d-block">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">
                  {{ __('Filter!') }}
                </button>
              </div>
              <div class="col-sm-2">
                <label class="form-label d-block">&nbsp;</label>
                <a href="{{ route('events') }}" class="btn btn-secondary w-100">
                  {{ __('Reset') }}
                </a>
              </div>
              @if(request('level'))
              <input type="hidden" name="level" value="{{ request('level') }}">
              @endif
              @if(request('server_id'))
              <input type="hidden" name="server_id" value="{{ request('server_id') }}">
              @endif
            </form>
          </div>
        </div>
      </div>
    </div>
    @include('theme::iframes.timeline._timeline')
  </div>
  @include('theme::iframes._scripts')
</x-layouts.app>
