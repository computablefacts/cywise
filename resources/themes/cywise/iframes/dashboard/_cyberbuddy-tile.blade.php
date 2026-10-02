{{-- CyberBuddy shortcut (dark tile), shared by both dashboard layouts --}}
@if(Auth::user()->canView('iframes.cyberbuddy'))
  <a href="{{ route('cyberbuddy') }}"
     class="ui:group ui:flex ui:items-center ui:gap-3 ui:rounded-xl ui:bg-ink ui:px-4 ui:py-3 ui:no-underline! ui:shadow-xs">
    <span class="ui:flex ui:size-9 ui:shrink-0 ui:items-center ui:justify-center ui:rounded-full ui:bg-brand-500 ui:text-white!">
      <x-phosphor-robot-bold class="ui:size-4"/>
    </span>
    {{-- Short title + one-line question: same height as the KPI tiles next to it --}}
    <span class="ui:flex ui:min-w-0 ui:flex-1 ui:flex-col">
      <span class="ui:truncate ui:text-sm ui:leading-5 ui:font-semibold ui:text-white!">{{ __('Ask :name', ['name' => tenant_custom_text('CyberBuddy')]) }}</span>
      <span class="ui:truncate ui:text-xs ui:leading-4 ui:text-slate-300!">{{ __('Do you have a question related to Cyber?') }}</span>
    </span>
    <x-phosphor-arrow-right class="ui:size-4 ui:shrink-0 ui:text-slate-400! ui:transition-transform ui:group-hover:translate-x-0.5"/>
  </a>
@endif
