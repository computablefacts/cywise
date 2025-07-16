<?php

use function Laravel\Folio\{middleware, name};

middleware('auth');
name('dashboard');
?>

<x-layouts.app>
  <iframe src="{{ route('iframes.dashboard') }}" class="w-full h-screen border-0"></iframe>
</x-layouts.app>
