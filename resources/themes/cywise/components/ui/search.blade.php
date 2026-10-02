{{--
  Instant "Ctrl+F" over a list: keeps the rows containing every typed word, as you type.
  Matched text: the row content (hidden details included) + its data-search attribute (e.g. tags, "28/09/2026").

  <x-ui.search target="#vulnerabilities-list"/>
  <x-ui.search target="#vulnerabilities-list" :reset="route('vulnerabilities')"/>   reset: link shown when the page is filtered by URL (e.g. ?asset_id=42)

  Inside the target:
    [data-search]        a row (li, tr…)
    [data-search-group]  a group of rows (e.g. a domain): kept, and opened, when one of its rows matches
    [data-search-empty]  shown when nothing matches
    [data-search-count]  live count of the matching rows of a list, e.g. data-search-count="#inventory-assets"

  Filtering: searchRows (iframes/_scripts).
--}}
@props([
  'target',
  'placeholder' => __('Search: asset, tag, CVE, email, date…'),
  'reset' => null,
])

<div x-data="{ q: '', shown: 0, total: 0 }"
     x-effect="Object.assign($data, searchRows(document.querySelector(@js($target)), q))"
     {{ $attributes->merge(['class' => 'ui:flex ui:items-center ui:gap-3 ui:rounded-xl ui:border ui:border-solid ui:border-line ui:bg-surface ui:px-4 ui:shadow-xs ui:focus-within:border-brand-500 ui:focus-within:ring-2 ui:focus-within:ring-brand-100']) }}>
  <x-phosphor-magnifying-glass class="ui:size-4 ui:shrink-0 ui:text-slate-400"/>
  <input type="search" x-model.debounce.150ms="q" placeholder="{{ $placeholder }}" aria-label="{{ __('Search') }}"
         class="ui:h-11 ui:min-w-0 ui:flex-1 ui:border-0 ui:bg-transparent ui:p-0 ui:text-sm ui:text-ink ui:placeholder:text-slate-400 ui:focus:outline-none! ui:focus:ring-0!">
  <span x-show="q" x-cloak class="ui:shrink-0 ui:text-xs ui:tabular-nums ui:text-slate-500" x-text="`${shown} / ${total}`"></span>
  @if($reset)
    <a href="{{ $reset }}" class="ui:flex ui:shrink-0 ui:items-center ui:gap-1 ui:text-xs ui:font-medium ui:text-brand-600! ui:no-underline! ui:hover:text-brand-700!">
      <x-phosphor-x class="ui:size-3.5"/>
      {{ __('Reset filters') }}
    </a>
  @endif
</div>
