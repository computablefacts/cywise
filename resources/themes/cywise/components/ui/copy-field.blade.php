{{--
  Read-only value with a copy button (install commands, webhooks).

  <x-ui.copy-field :value="$command"/>
  <x-ui.copy-field bind="webhookUrl" :placeholder="__('Save first')"/>   bind: Alpine expression of the parent scope, live value

  Focus, ring and outline are reset: BlueprintJS and the forms plugin add a blue one on inputs.
--}}
@props([
  'value' => '',
  'bind' => null,
  'placeholder' => null,
])

<div x-data="{ copied: false, feedbackMs: 2000 }"
     {{ $attributes->merge(['class' => 'ui:flex ui:items-stretch ui:overflow-hidden ui:rounded-lg ui:border ui:border-solid ui:border-line ui:bg-slate-50']) }}>
  <input type="text" readonly value="{{ $value }}" x-ref="field" placeholder="{{ $placeholder }}"
         @if($bind) :value="{{ $bind }}" @endif
         @focus="$el.select()"
         class="ui:min-w-0 ui:flex-1 ui:border-0 ui:bg-transparent ui:px-3 ui:py-2 ui:font-mono ui:text-xs ui:text-slate-700 ui:placeholder:font-sans ui:placeholder:text-slate-400 ui:outline-none! ui:focus:ring-0!">
  <button type="button" @if($bind) :disabled="!({{ $bind }})" @endif
          @click="navigator.clipboard.writeText($refs.field.value); copied = true; setTimeout(() => copied = false, feedbackMs)"
          class="ui:flex ui:items-center ui:gap-1.5 ui:border-0 ui:border-l ui:border-solid ui:border-line ui:bg-white ui:px-3 ui:text-xs ui:font-medium ui:text-slate-700 ui:hover:bg-slate-100 ui:cursor-pointer ui:disabled:cursor-not-allowed ui:disabled:opacity-50">
    <x-phosphor-copy x-show="!copied" class="ui:size-4"/>
    <x-phosphor-check x-show="copied" x-cloak class="ui:size-4 ui:text-low"/>
    <span x-text="copied ? @js(__('Copied')) : @js(__('Copy'))">{{ __('Copy') }}</span>
  </button>
</div>
