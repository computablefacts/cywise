{{--
  Read-only value with a copy button (install commands, webhooks).

  <x-ui.copy-field :value="$command"/>
--}}
@props([
  'value' => '',
])

<div x-data="{ copied: false, feedbackMs: 2000 }"
     {{ $attributes->merge(['class' => 'ui:flex ui:items-stretch ui:overflow-hidden ui:rounded-lg ui:border ui:border-solid ui:border-line ui:bg-slate-50']) }}>
  <input type="text" readonly value="{{ $value }}" x-ref="field"
         @focus="$el.select()"
         class="ui:min-w-0 ui:flex-1 ui:border-0 ui:bg-transparent ui:px-3 ui:py-2 ui:font-mono ui:text-xs ui:text-slate-700 ui:outline-none">
  <button type="button"
          @click="navigator.clipboard.writeText($refs.field.value); copied = true; setTimeout(() => copied = false, feedbackMs)"
          class="ui:flex ui:items-center ui:gap-1.5 ui:border-0 ui:border-l ui:border-solid ui:border-line ui:bg-white ui:px-3 ui:text-xs ui:font-medium ui:text-slate-700 ui:hover:bg-slate-100 ui:cursor-pointer">
    <x-phosphor-copy x-show="!copied" class="ui:size-4"/>
    <x-phosphor-check x-show="copied" x-cloak class="ui:size-4 ui:text-low"/>
    <span x-text="copied ? @js(__('Copied')) : @js(__('Copy'))">{{ __('Copy') }}</span>
  </button>
</div>
