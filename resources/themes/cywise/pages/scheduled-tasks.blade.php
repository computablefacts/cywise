<?php

use function Laravel\Folio\{middleware, name};

middleware('auth');
name('scheduled-tasks');
?>

<x-layouts.app>
  <iframe src="{{ route('iframes.scheduled-tasks') }}" class="w-full h-screen border-0"></iframe>
</x-layouts.app>