<?php

use function Laravel\Folio\{middleware, name};

middleware('auth');
name('assets');
?>

<x-layouts.app>
  <iframe src="{{ route('iframes.assets') }}" class="w-full h-screen border-0"></iframe>
</x-layouts.app>

