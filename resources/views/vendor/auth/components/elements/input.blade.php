{{--
  Labelled text input. Keeps the package hooks: wire:model, autofocus, the "focus-{id}" window event (login steps).
--}}
@props([
    'label' => null,
    'id' => null,
    'name' => null,
    'type' => 'text',
    'autofocus' => false
])

@php
    $wireModel = $attributes->get('wire:model');
    $hasError = $errors->has($wireModel);
@endphp

<div x-data x-init="@if($autofocus ?? false) setTimeout(function(){ $refs.input.focus(); }, 1); @endif" class="ui:flex ui:w-full ui:flex-col ui:gap-1.5">
    @if($label)
        <label for="{{ $id ?? '' }}" class="ui:text-sm ui:font-medium ui:text-slate-700">{{ __($label) }}</label>
    @endif
    <input {{ $attributes }} @focus-{{ $id }}.window="$el.focus()" id="{{ $id ?? '' }}" name="{{ $name ?? '' }}" type="{{ $type ?? '' }}" x-ref="input"
           class="auth-component-input ui:box-border ui:h-12 ui:w-full ui:rounded-xl ui:border ui:border-solid ui:bg-white ui:px-4 ui:text-[15px] ui:text-ink ui:outline-none! ui:placeholder:text-slate-400 ui:focus:ring-2 ui:disabled:cursor-not-allowed ui:disabled:opacity-50 {{ $hasError ? 'ui:border-critical ui:focus:ring-critical-soft' : 'ui:border-line ui:focus:border-brand-500 ui:focus:ring-brand-100' }}"/>
    @error($wireModel)
        <p class="ui:m-0 ui:text-sm ui:text-critical">{{ $message }}</p>
    @enderror
</div>
