{{--
  Collapsible sidebar group (keeps the menu short enough to avoid scrolling).

  state:
    active  current page is inside → always open on load
    open    open by default, then the user's last choice (localStorage)
    closed  closed by default, then the user's last choice (localStorage)

  <x-app.sidebar-section id="configuration" :title="__('Configuration')" state="closed">…links…</x-app.sidebar-section>
--}}
@props([
  'id',
  'title',
  'state' => 'closed', // active | open | closed
])

@php
  $key = "sidebar-section-{$id}";
  $default = $state === 'closed' ? 'false' : 'true';
@endphp

{{-- Storage can throw (private mode, blocked site data): fall back to the default --}}
<div x-data="{
       open: {{ $default }},
       init() {
         @if($state !== 'active')
         try { this.open = (localStorage.getItem('{{ $key }}') ?? '{{ $default }}') === 'true'; } catch (e) {}
         @endif
         this.$watch('open', (v) => { try { localStorage.setItem('{{ $key }}', v); } catch (e) {} });
       },
     }"
     class="ui:flex ui:flex-col ui:gap-0.5">
  <button type="button" @click="open = !open" :aria-expanded="open"
          class="ui:flex ui:w-full ui:items-center ui:justify-between ui:rounded-md ui:border-0 ui:bg-transparent ui:px-3 ui:py-1 ui:text-[11px] ui:font-semibold ui:uppercase ui:tracking-wider ui:text-slate-400 ui:cursor-pointer ui:hover:text-ink">
    <span>{{ $title }}</span>
    <x-phosphor-caret-down class="ui:size-3 ui:transition-transform" ::class="!open && 'ui:-rotate-90'"/>
  </button>
  <div x-show="open" x-collapse {{ $default === 'false' ? 'x-cloak' : '' }} class="ui:flex ui:flex-col ui:gap-0.5">
    {{ $slot }}
  </div>
</div>
