<?php

use App\Http\Middleware\CheckPermissionsHttpRequest;
use App\Http\Middleware\LogHttpRequests;
use function Laravel\Folio\{middleware, name};

middleware([LogHttpRequests::class, 'auth', CheckPermissionsHttpRequest::class]);
name('tables');
?>

<x-layouts.app>
  <div class="ui:mx-auto ui:flex ui:w-full ui:max-w-7xl ui:flex-col ui:gap-6 ui:px-4 ui:py-8 ui:lg:px-8">

    <x-ui.page-header :title="__('Tables')" :subtitle="__('Data imported from your files and queryable in SQL.')">
      <x-slot:actions>
        <x-ui.button icon="plus" :href="route('table')">{{ __('New table') }}</x-ui.button>
      </x-slot:actions>
    </x-ui.page-header>

    {{-- Legacy list shared with the SQL editor: rows filled by JS --}}
    <x-ui.card flush>
      <x-tables-list/>
    </x-ui.card>
  </div>
</x-layouts.app>
