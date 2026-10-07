<?php

use App\Http\Middleware\CheckPermissionsHttpRequest;
use App\Http\Middleware\LogHttpRequests;
use function Laravel\Folio\{middleware, name};

middleware([LogHttpRequests::class, 'auth', CheckPermissionsHttpRequest::class]);
name('documentation');
?>

<x-layouts.app>
  <div class="ui:mx-auto ui:flex ui:w-full ui:max-w-7xl ui:flex-col ui:gap-6 ui:px-4 ui:py-8 ui:lg:px-8">

    <x-ui.page-header :title="__('Documentation')"
                      :subtitle="__('Guides to use Cywise and its API.')"/>

    <div class="ui:grid ui:gap-4 ui:md:grid-cols-2">
      <x-ui.card :title="__('API')" :subtitle="__('Reference of the API endpoints.')">
        <x-ui.button variant="secondary" icon="arrow-square-out" target="_blank"
                     :href="route('v2.private.rpc.docs')">
          {{ __('Open the documentation') }}
        </x-ui.button>
      </x-ui.card>
      <x-ui.card :title="__('Data Management')" :subtitle="__('User guide of the interface.')">
        <x-ui.button variant="secondary" icon="arrow-square-out" target="_blank"
                     href="https://computablefacts.notion.site/Guide-utilisateur-2160a1f68ecc80689497e7dd5c07a817?source=copy_link">
          {{ __('Open the documentation') }}
        </x-ui.button>
      </x-ui.card>
    </div>
  </div>
</x-layouts.app>
