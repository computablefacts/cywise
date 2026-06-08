<?php

use App\Http\Controllers\Iframes\TablesController;
use App\Http\Middleware\CheckPermissionsHttpRequest;
use App\Http\Middleware\LogHttpRequests;
use Illuminate\Http\Request;
use function Laravel\Folio\{middleware, name, render};

middleware([LogHttpRequests::class, 'auth', CheckPermissionsHttpRequest::class]);
name('tables');
render(function (Request $request) {
  return app(TablesController::class)($request);
});
?>

<x-layouts.app>
  <div class="container-fluid">
    <h6 class="m-0 mt-3 mb-3">
      <a href="{{ route('table') }}">
        {{ __('+ new') }}
      </a>
    </h6>
    <div class="card mt-3 mb-3">
      <div class="card-body p-0">
        <x-tables-list/>
      </div>
    </div>
  </div>
</x-layouts.app>

