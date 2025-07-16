<?php

use function Laravel\Folio\{middleware, name};

middleware('auth');
name('collections');
?>

<x-layouts.app>
  <iframe src="{{ route('iframes.collections') }}" class="w-full h-screen border-0"></iframe>
</x-layouts.app>

