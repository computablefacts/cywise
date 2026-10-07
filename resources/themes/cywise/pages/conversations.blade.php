<?php

use App\Http\Controllers\Iframes\ConversationsController;
use App\Http\Middleware\CheckPermissionsHttpRequest;
use App\Http\Middleware\LogHttpRequests;
use Illuminate\Http\Request;
use function Laravel\Folio\{middleware, name, render};

middleware([LogHttpRequests::class, 'auth', CheckPermissionsHttpRequest::class]);
name('conversations');
render(function (Request $request) {
  return app(ConversationsController::class)($request);
});
?>

<x-layouts.app>
  @php
    // Timeline items (date → time → events) flattened, most recent first.
    $rows = collect($items)
      ->flatMap(fn($times) => collect($times)->flatMap(fn($events) => $events))
      ->values();
  @endphp

  <div class="ui:mx-auto ui:flex ui:w-full ui:max-w-7xl ui:flex-col ui:gap-6 ui:px-4 ui:py-8 ui:lg:px-8">

    <x-ui.page-header :title="__('Conversations')"
                      :subtitle="__('Your conversations with CyberBuddy, most recent first.')">
      <x-slot:actions>
        <x-ui.button icon="plus" :href="route('cyberbuddy')">{{ __('New conversation') }}</x-ui.button>
      </x-slot:actions>
    </x-ui.page-header>

    {{-- Ctrl+F like search: author, description, date… --}}
    <x-ui.search target="#conversations-list" :placeholder="__('Search: description, date…')"/>

    {{-- List --}}
    <x-ui.card id="conversations-list" flush :title="trans_choice(':count conversation|:count conversations', $nb_conversations, ['count' => $nb_conversations])">
      @if($rows->isEmpty())
        <x-ui.empty icon="chat-circle">{{ __('No conversation yet.') }}</x-ui.empty>
      @else
        <ul class="ui:m-0 ui:list-none ui:p-0">
          @foreach($rows as $row)
            {!! $row['html'] !!}
          @endforeach
        </ul>
        <x-ui.empty icon="magnifying-glass" data-search-empty style="display: none">{{ __('No result for this search.') }}</x-ui.empty>
      @endif
    </x-ui.card>
  </div>

  @include('theme::iframes._scripts')
</x-layouts.app>
