<?php

use function Laravel\Folio\{middleware, name};

middleware('auth');
name('cyberscribe');
?>

<x-layouts.app>
  <iframe src="{{ route('iframes.cyberscribe') }}" class="w-full h-screen border-0"></iframe>
</x-layouts.app>

