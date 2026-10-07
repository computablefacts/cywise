<?php

use App\Http\Controllers\Iframes\NotesAndMemosController;
use App\Http\Middleware\CheckPermissionsHttpRequest;
use App\Http\Middleware\LogHttpRequests;
use Illuminate\Http\Request;
use function Laravel\Folio\{middleware, name, render};

middleware([LogHttpRequests::class, 'auth', CheckPermissionsHttpRequest::class]);
name('notes-and-memos');
render(function (Request $request) {
  return app(NotesAndMemosController::class)($request);
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

    <x-ui.page-header :title="__('Notes & Memos')"
                      :subtitle="__('Notes shared with CyberBuddy and the SOC operator, most recent first.')"/>

    {{-- New note: .new-comment input and .note-scope are wired by iframes/_scripts (Enter submits) --}}
    <x-ui.card>
      <div class="new-comment ui:flex ui:flex-col ui:gap-3">
        <x-ui.input :placeholder="__('Add a note... (press Enter to submit)')"/>
        <div class="ui:flex ui:flex-wrap ui:gap-x-6 ui:gap-y-2 ui:text-sm ui:text-slate-600">
          <label for="scopeCyberBuddy" class="ui:m-0 ui:inline-flex ui:items-center ui:gap-2 ui:cursor-pointer">
            <input class="note-scope ui:size-4 ui:cursor-pointer ui:accent-brand-500" type="checkbox" id="scopeCyberBuddy" value="CyberBuddy" checked>
            {{ __('Note générale') }}
          </label>
          <label for="scopeSOC" class="ui:m-0 ui:inline-flex ui:items-center ui:gap-2 ui:cursor-pointer">
            <input class="note-scope ui:size-4 ui:cursor-pointer ui:accent-brand-500" type="checkbox" id="scopeSOC" value="SOC Operator">
            {{ __("Note dédiée à l'opérateur SOC") }}
          </label>
        </div>
      </div>
    </x-ui.card>

    {{-- Ctrl+F like search: subject, body, scope, date… --}}
    <x-ui.search target="#notes-list" :placeholder="__('Search: subject, content, date…')"/>

    {{-- List --}}
    <x-ui.card id="notes-list" flush :title="trans_choice(':count note|:count notes', $nb_notes, ['count' => $nb_notes])">
      {{--
        iframes/_scripts inserts a created note at the top of the list following #sid-{today}:
          <span id="sid-2026-10-06"> + <ul> ← new <li> goes first
        Empty state: shown while the list has no other row (:only-child).
      --}}
      <span id="sid-{{ now()->utc()->format('Y-m-d') }}" hidden></span>
      <ul class="ui:m-0 ui:list-none ui:p-0">
        @foreach($rows as $row)
          {!! $row['html'] !!}
        @endforeach
        <li class="ui:hidden ui:only:block">
          <x-ui.empty icon="note-pencil">{{ __('No note yet.') }}</x-ui.empty>
        </li>
      </ul>
      @if($rows->isNotEmpty())
        <x-ui.empty icon="magnifying-glass" data-search-empty style="display: none">{{ __('No result for this search.') }}</x-ui.empty>
      @endif
    </x-ui.card>
  </div>

  @include('theme::iframes._scripts')
</x-layouts.app>
