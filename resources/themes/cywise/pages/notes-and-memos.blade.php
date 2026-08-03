<?php

use App\Http\Controllers\Iframes\NotesAndMemosController;
use App\Http\Middleware\CheckPermissionsHttpRequest;
use App\Http\Middleware\LogHttpRequests;
use Illuminate\Http\Request;
use function Laravel\Folio\{middleware, name, render};

middleware([LogHttpRequests::class, 'auth', CheckPermissionsHttpRequest::class]);
name('notes-and-memos');
render(function (Request $request) {
  return app(NotesAndMemosController::class)($request);
});
?>

<x-layouts.app>
  @include('theme::iframes._styles')
  <div class="container-fluid">
    @include('theme::iframes.timeline._note-counters')
    @include('theme::iframes.timeline._timeline')
  </div>
  @include('theme::iframes._scripts')
</x-layouts.app>
