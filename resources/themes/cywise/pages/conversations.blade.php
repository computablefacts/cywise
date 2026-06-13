<?php

use App\Http\Controllers\Iframes\ConversationsController;
use App\Http\Middleware\CheckPermissionsHttpRequest;
use App\Http\Middleware\LogHttpRequests;
use Illuminate\Http\Request;
use function Laravel\Folio\{middleware, name, render};

middleware([LogHttpRequests::class, 'auth', CheckPermissionsHttpRequest::class]);
name('conversations');
render(function (Request $request) {
  return app(ConversationsController::class)($request);
});
?>

<x-layouts.app>
  @include('theme::iframes._styles')
  <div class="container-fluid">
    @include('theme::iframes.timeline._conversation-counters')
    @include('theme::iframes.timeline._timeline')
  </div>
  @include('theme::iframes._scripts')
</x-layouts.app>
