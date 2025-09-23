<?php

use function Laravel\Folio\{middleware, name};

middleware('auth');
name('roles-and-permissions');
?>

<x-layouts.app>
  <iframe src="{{ route('iframes.roles-and-permissions') }}" class="w-full h-screen border-0"></iframe>
</x-layouts.app>
