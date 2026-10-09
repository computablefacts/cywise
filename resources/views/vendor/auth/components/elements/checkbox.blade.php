@props([
    'label' => null,
    'name' => null,
    'id' => null,
])

<label for="{{ $id ?? '' }}" class="ui:flex ui:cursor-pointer ui:select-none ui:items-center ui:gap-2.5 ui:text-sm ui:text-slate-700">
    <input type="checkbox" {{ $attributes->whereStartsWith('wire:model') }} id="{{ $id ?? '' }}" name="{{ $name ?? '' }}" class="ui:size-4 ui:accent-brand-500">
    {{ __($label ?? '') }}
</label>
