<?php

use function Laravel\Folio\{middleware, name};

middleware('auth');
name('tables');
?>

<x-layouts.app>
  <iframe src="{{ route('iframes.tables') }}" class="w-full h-screen border-0"></iframe>
</x-layouts.app>

