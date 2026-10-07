{{--
  Multi-line text input, same look as x-ui.input.

  <x-ui.textarea id="prompt" name="prompt" rows="6">{{ $value }}</x-ui.textarea>
--}}
<textarea {{ $attributes->merge(['rows' => 4, 'class' => 'ui:box-border ui:w-full ui:min-w-0 ui:rounded-lg ui:border ui:border-solid ui:border-line ui:bg-white ui:px-3 ui:py-2 ui:text-sm ui:text-ink ui:placeholder:text-slate-400 ui:outline-none! ui:focus:border-brand-500 ui:focus:ring-2 ui:focus:ring-brand-100']) }}>{{ $slot }}</textarea>
