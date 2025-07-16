<?php

use function Laravel\Folio\{middleware, name};

middleware('auth');
name('notes-and-memos');
?>

<x-layouts.app>
  <iframe src="{{ route('iframes.notes-and-memos') }}" class="w-full h-screen border-0"></iframe>
</x-layouts.app>

