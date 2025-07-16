<?php

use function Laravel\Folio\{middleware, name};

middleware('auth');
name('prompts');
?>

<x-layouts.app>
  <iframe src="{{ route('iframes.prompts') }}" class="w-full h-screen border-0"></iframe>
</x-layouts.app>

