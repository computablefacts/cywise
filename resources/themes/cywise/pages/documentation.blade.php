<?php

use function Laravel\Folio\{middleware, name};

middleware('auth');
name('documentation');
?>

<x-layouts.app>
  <iframe src="{{ route('iframes.documentation') }}" class="w-full h-screen border-0"></iframe>
</x-layouts.app>

