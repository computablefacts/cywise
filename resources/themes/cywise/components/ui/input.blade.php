{{--
  Text input, with the same focus ring as the topbar field.

  <x-ui.input id="tg-bot-token" x-model="token" placeholder="1234567890:ABCDEF…"/>
--}}
<input {{ $attributes->merge(['type' => 'text', 'class' => 'ui:box-border ui:h-10 ui:w-full ui:min-w-0 ui:rounded-lg ui:border ui:border-solid ui:border-line ui:bg-white ui:px-3 ui:text-sm ui:text-ink ui:placeholder:text-slate-400 ui:outline-none! ui:focus:border-brand-500 ui:focus:ring-2 ui:focus:ring-brand-100']) }}>
