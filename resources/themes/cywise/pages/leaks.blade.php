<?php

use function Laravel\Folio\{middleware, name};

middleware('auth');
name('leaks');
?>

<x-layouts.app>
  <iframe src="{{ route('iframes.leaks') }}" class="w-full h-screen border-0"></iframe>
</x-layouts.app>

