<?php

use function Laravel\Folio\{middleware, name};

middleware('auth');
name('traces');
?>

<x-layouts.app>
  <iframe src="{{ route('iframes.traces') }}" class="w-full h-screen border-0"></iframe>
</x-layouts.app>

