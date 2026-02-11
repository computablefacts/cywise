<?php

use function Laravel\Folio\{middleware, name};

middleware('auth');
name('actions.editor');
?>

<x-layouts.app>
  <iframe src="{{ route('iframes.actions-editor', ['action_id' => request('action_id')]) }}"
          class="w-full h-screen border-0"></iframe>
</x-layouts.app>
