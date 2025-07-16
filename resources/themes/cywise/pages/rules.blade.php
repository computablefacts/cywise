<?php

use function Laravel\Folio\{middleware, name};

middleware('auth');
name('rules');
?>

<x-layouts.app>
  <iframe src="{{ route('iframes.rules') }}" class="w-full h-screen border-0"></iframe>
</x-layouts.app>

