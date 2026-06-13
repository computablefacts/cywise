<?php

use App\Http\Controllers\Iframes\LeaksController;
use App\Http\Middleware\CheckPermissionsHttpRequest;
use App\Http\Middleware\LogHttpRequests;
use Illuminate\Http\Request;
use function Laravel\Folio\{middleware, name, render};

middleware([LogHttpRequests::class, 'auth', CheckPermissionsHttpRequest::class]);
name('leaks');
render(function (Request $request) {
  return app(LeaksController::class)($request);
});
?>

<x-layouts.app>
  @include('theme::iframes._styles')
  <div class="container-fluid">
    @include('theme::iframes.timeline._leak-counters')
    @include('theme::iframes.timeline._timeline')
  </div>
  @include('theme::iframes._scripts')
</x-layouts.app>
