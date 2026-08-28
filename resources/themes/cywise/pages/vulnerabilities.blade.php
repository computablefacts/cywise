<?php

use App\Http\Controllers\Iframes\VulnerabilitiesController;
use App\Http\Middleware\CheckPermissionsHttpRequest;
use App\Http\Middleware\LogHttpRequests;
use Illuminate\Http\Request;
use function Laravel\Folio\{middleware, name, render};

middleware([LogHttpRequests::class, 'auth', CheckPermissionsHttpRequest::class]);
name('vulnerabilities');
render(function (Request $request) {
  return app(VulnerabilitiesController::class)($request);
});
?>

<x-layouts.app>
  @include('theme::iframes._styles')
  <div class="container-fluid">
    @include('theme::iframes.timeline._vulnerability-counters')
    <div class="row mt-3 mb-1">
      <div class="col">
        <div class="card">
          <div class="card-body p-3">
            <form method="get" action="{{ route('vulnerabilities') }}" class="row g-2 align-items-end">
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
              <div class="col-sm-3">
                <label for="port_tags" class="form-label">
                  {{ __('System tag') }}
                </label>
                <select id="port_tags" name="port_tags" class="form-select">
                  <option value="">{{ __('All tags') }}</option>
                  @foreach($port_tags as $tag)
                  <option value="{{ $tag }}" {{ request(
                  'port_tags') === $tag ? 'selected' : '' }}>
                  {{ $tag }}
                  </option>
                  @endforeach
                </select>
              </div>
              <div class="col-sm-1">
                <label class="form-label d-block">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">
                  {{ __('Filter!') }}
                </button>
              </div>
              <div class="col-sm-2">
                <label class="form-label d-block">&nbsp;</label>
                <a href="{{ route('vulnerabilities') }}" class="btn btn-secondary w-100">
                  {{ __('Reset') }}
                </a>
              </div>
              @if(request('level'))
              <input type="hidden" name="level" value="{{ request('level') }}">
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
