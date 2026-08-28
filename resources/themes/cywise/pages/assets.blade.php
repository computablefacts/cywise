<?php

use App\Http\Controllers\Iframes\AssetsController;
use App\Http\Middleware\CheckPermissionsHttpRequest;
use App\Http\Middleware\LogHttpRequests;
use Illuminate\Http\Request;
use function Laravel\Folio\{middleware, name, render};

middleware([LogHttpRequests::class, 'auth', CheckPermissionsHttpRequest::class]);
name('assets');
render(function (Request $request) {
  return app(AssetsController::class)($request);
});
?>

<x-layouts.app>
  @include('theme::iframes._styles')
  <div class="container-fluid">
    @include('theme::iframes.timeline._asset-counters')
    <div class="row mt-3 mb-1">
      <div class="col">
        <div class="card">
          <div class="card-body p-3">
            <form method="get" action="{{ route('assets') }}" class="row g-2 align-items-end">
              <div class="col-sm-3">
                <label for="tld" class="form-label">
                  {{ __('Asset') }}
                </label>
                <input type="text"
                       id="tld"
                       name="tld"
                       value="{{ request('tld') }}"
                       class="form-control"
                       placeholder="example.com">
              </div>
              <div class="col-sm-3">
                <label for="tags" class="form-label">
                  {{ __('User tag') }}
                </label>
                <select id="tags" name="tags" class="form-select">
                  <option value="">{{ __('All tags') }}</option>
                  @foreach($tags as $tag)
                  <option value="{{ $tag }}" {{ request(
                  'tags') === $tag ? 'selected' : '' }}>
                  {{ $tag }}
                  </option>
                  @endforeach
                </select>
              </div>
              <div class="col-sm-2">
                <label for="monitoring_type" class="form-label">
                  {{ __('Monitoring Type') }}
                </label>
                <select id="monitoring_type" name="monitoring_type" class="form-select">
                  <option value="">{{ __('All monitoring types') }}</option>
                  <option value="internal" {{ request(
                  'monitoring_type') === 'internal' ? 'selected' : '' }}>
                  {{ __('Internal (Agent)') }}
                  </option>
                  <option value="external" {{ request(
                  'monitoring_type') === 'external' ? 'selected' : '' }}>
                  {{ __('External (Scanner)') }}
                  </option>
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
                <a href="{{ route('assets') }}" class="btn btn-secondary w-100">
                  {{ __('Reset') }}
                </a>
              </div>
              @if(request('status'))
              <input type="hidden" name="status" value="{{ request('status') }}">
              @endif
              @if(request('asset_id'))
              <input type="hidden" name="asset_id" value="{{ request('asset_id') }}">
              @endif
            </form>
          </div>
        </div>
      </div>
    </div>
    @include('theme::iframes.timeline._timeline')
    @include('theme::iframes.timeline._share-modal')
  </div>
  @include('theme::iframes._scripts')
</x-layouts.app>
