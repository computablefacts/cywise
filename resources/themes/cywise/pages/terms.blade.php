<?php

use function Laravel\Folio\{name};

name('terms');
?>

<x-layouts.app>
  <iframe src="{{ route('iframes.terms') }}" class="w-full h-screen border-0"></iframe>
</x-layouts.app>

