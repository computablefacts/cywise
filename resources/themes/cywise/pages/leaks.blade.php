<?php

use App\Http\Controllers\Iframes\LeaksController;
use App\Http\Middleware\CheckPermissionsHttpRequest;
use App\Http\Middleware\LogHttpRequests;
use Illuminate\Http\Request;
use function Laravel\Folio\{middleware, name, render};

middleware([LogHttpRequests::class, 'auth', CheckPermissionsHttpRequest::class]);
name('leaks');
render(function (Request $request) {
  return app(LeaksController::class)($request);
});
?>

<x-layouts.app>
  @php
    // Timeline items (date → time → batches of leaks) flattened, most recent first.
    $rows = collect($items)
      ->flatMap(fn($times) => collect($times)->flatMap(fn($events) => $events))
      ->values();

    $isFiltered = !empty(array_filter(request()->only(['tld', 'tags', 'asset_id'])));
  @endphp

  <div class="ui:mx-auto ui:flex ui:w-full ui:max-w-7xl ui:flex-col ui:gap-6 ui:px-4 ui:py-8 ui:lg:px-8">

    <x-ui.page-header :title="__('Leaks')"
                      :subtitle="__('Credentials of your domains found in data leaks and infostealer logs.')"/>

    {{-- What to do --}}
    @if($nb_leaks > 0)
      <div class="ui:flex ui:items-start ui:gap-3 ui:rounded-xl ui:border ui:border-solid ui:border-red-200 ui:bg-high-soft ui:p-4">
        <span class="ui:flex ui:size-9 ui:shrink-0 ui:items-center ui:justify-center ui:rounded-lg ui:bg-high ui:text-white">
          <x-phosphor-user-focus-bold class="ui:size-5"/>
        </span>
        <p class="ui:m-0 ui:text-sm ui:text-red-900">
          {!! __('We have found <b>:count leaked or compromised</b> identifiers. If no action has been taken yet, ask the affected users to change their passwords.', ['count' => $nb_leaks]) !!}
        </p>
      </div>
    @endif

    {{-- Ctrl+F like search: email, website, date, source… --}}
    <x-ui.search target="#leaks-list" :placeholder="__('Search: email, website, date…')"
                 :reset="$isFiltered ? route('leaks') : null"/>

    {{-- Table --}}
    <x-ui.card id="leaks-list" flush :title="trans_choice(':count leaked credential|:count leaked credentials', $nb_leaks, ['count' => $nb_leaks])">
      @if($rows->isEmpty())
        <x-ui.empty>
          {{ $isFiltered ? __('No leak matches these filters.') : __('Good job! No passwords to change.') }}
        </x-ui.empty>
      @else
        <div class="ui:overflow-x-auto">
          {{-- Click a header to sort the rows (client side: all rows are on the page). Dates are ISO: text order works. --}}
          <table class="ui:w-full ui:border-collapse ui:text-sm"
                 x-data="{
                   col: 0,
                   asc: false,
                   sort(i) {
                     this.asc = this.col === i ? !this.asc : true;
                     this.col = i;
                     const body = this.$refs.body;
                     const text = (r) => r.cells[i].innerText.trim();
                     [...body.rows]
                       .sort((a, b) => (this.asc ? 1 : -1) * text(a).localeCompare(text(b), 'fr', { numeric: true }))
                       .forEach((r) => body.appendChild(r));
                   },
                 }">
            <thead>
              <tr class="ui:bg-slate-50 ui:text-left ui:text-xs ui:uppercase ui:tracking-wide ui:text-slate-500">
                @foreach([0 => __('Date'), 1 => __('Email'), 2 => __('Website'), 3 => __('Password'), 4 => __('Source')] as $i => $label)
                  <th class="{{ $i === 0 ? 'ui:w-px ' : '' }}ui:whitespace-nowrap ui:px-4 ui:py-2 ui:font-medium" :aria-sort="col === {{ $i }} ? (asc ? 'ascending' : 'descending') : 'none'">
                    <button type="button" @click="sort({{ $i }})"
                            class="ui:inline-flex ui:items-center ui:gap-1 ui:border-0 ui:bg-transparent ui:p-0 ui:text-xs ui:font-medium ui:uppercase ui:tracking-wide ui:text-slate-500 ui:cursor-pointer ui:hover:text-ink">
                      {{ $label }}
                      <x-phosphor-caret-up class="ui:size-3" x-show="col === {{ $i }} && asc" x-cloak/>
                      <x-phosphor-caret-down class="ui:size-3" x-show="col === {{ $i }} && !asc" x-cloak/>
                    </button>
                  </th>
                @endforeach
              </tr>
            </thead>
            <tbody x-ref="body">
              @foreach($rows as $row)
                {!! $row['html'] !!}
              @endforeach
            </tbody>
          </table>
        </div>
        <x-ui.empty icon="magnifying-glass" data-search-empty style="display: none">{{ __('No result for this search.') }}</x-ui.empty>
      @endif
    </x-ui.card>
  </div>

  @include('theme::iframes._scripts')
</x-layouts.app>
