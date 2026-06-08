<?php

use App\Http\Middleware\CheckPermissionsHttpRequest;
use App\Http\Middleware\LogHttpRequests;
use function Laravel\Folio\{middleware, name};

middleware([LogHttpRequests::class, 'auth', CheckPermissionsHttpRequest::class]);
name('tables');
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

