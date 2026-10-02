{{--
  Labelled form control.

  <x-ui.field :label="__('Server')" for="server_id"><x-ui.select id="server_id" name="server_id">…</x-ui.select></x-ui.field>
--}}
@props([
  'label',
  'for' => null,
])

<div {{ $attributes->merge(['class' => 'ui:flex ui:min-w-0 ui:flex-col ui:gap-1.5']) }}>
  <label @if($for) for="{{ $for }}" @endif class="ui:m-0 ui:text-xs ui:font-medium ui:text-slate-600">{{ $label }}</label>
  {{ $slot }}
</div>
