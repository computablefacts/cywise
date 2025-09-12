<?php

use function Laravel\Folio\{middleware, name};

middleware('auth');
name('rules-editor');
?>

<x-layouts.app>
  <iframe src="{{ route('iframes.rules-editor') }}" class="w-full h-screen border-0"></iframe>
</x-layouts.app>

