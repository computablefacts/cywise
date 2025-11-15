<?php

use function Laravel\Folio\{middleware, name};

middleware('auth');
name('analyze');
?>

<x-layouts.app>
  <iframe src="{{ route('iframes.analyze') }}" class="w-full h-screen border-0"></iframe>
</x-layouts.app>

