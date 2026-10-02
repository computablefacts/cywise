{{-- Native select with our own caret. <x-ui.select name="tags"><option>…</option></x-ui.select> --}}
<div class="ui:relative ui:w-full">
  <select {{ $attributes->merge(['class' => 'ui:h-10 ui:w-full ui:cursor-pointer ui:appearance-none ui:rounded-lg ui:border ui:border-solid ui:border-line ui:bg-white ui:bg-none ui:pl-3 ui:pr-9 ui:text-sm ui:text-ink ui:shadow-xs ui:focus:border-brand-500 ui:focus:outline-none ui:focus:ring-2 ui:focus:ring-brand-100']) }}>
    {{ $slot }}
  </select>
  <x-phosphor-caret-down class="ui:pointer-events-none ui:absolute ui:right-3 ui:top-1/2 ui:size-4 ui:-translate-y-1/2 ui:text-slate-400"/>
</div>
