<?php

use function Laravel\Folio\{middleware, name};

middleware('auth');
name('shares');
?>

<x-layouts.app>
  <iframe src="{{ route('iframes.shares') }}" class="w-full h-screen border-0"></iframe>
</x-layouts.app>