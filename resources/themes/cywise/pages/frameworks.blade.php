<?php

use function Laravel\Folio\{middleware, name};

middleware('auth');
name('frameworks');
?>

<x-layouts.app>
  <iframe src="{{ route('iframes.frameworks') }}" class="w-full h-screen border-0"></iframe>
</x-layouts.app>

