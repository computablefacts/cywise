<label {{ $attributes->merge(['class' => 'd-flex align-items-center gap-2 cursor-pointer']) }}>
    <x-filament::input.checkbox {{ $attributes->except('class') }} />

    <span class="small">
        {{ $slot }}
    </span>
</label>