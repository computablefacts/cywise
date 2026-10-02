<?php

use App\Http\Controllers\Iframes\DashboardController;
use App\Http\Middleware\CheckPermissionsHttpRequest;
use App\Http\Middleware\LogHttpRequests;
use Illuminate\Http\Request;
use function Laravel\Folio\{middleware, name, render};

middleware([LogHttpRequests::class, 'auth', CheckPermissionsHttpRequest::class]);
name('dashboard');
render(function (Request $request) {
  return app(DashboardController::class)($request);
});
?>

<x-layouts.app>
  <div class="ui:mx-auto ui:w-full ui:max-w-7xl ui:px-4 ui:py-8 ui:lg:px-8">
    {{-- Two states: first visit (nothing monitored yet) vs. overview --}}
    @if($onboarding)
      @include('theme::iframes.dashboard._onboarding')
    @else
      @include('theme::iframes.dashboard._overview')
    @endif
  </div>
</x-layouts.app>
