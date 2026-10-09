{{-- Inline link (forgot password, sign up…). Plain-text slots come from config and are translated. --}}
@php
    $text = trim($slot->toHtml());
    $label = $text === strip_tags($text) ? __(html_entity_decode($text, ENT_QUOTES)) : null;
@endphp

<x-auth::elements.link
    {{ $attributes->except('wire:navigate') }}
    class="ui:cursor-pointer ui:font-semibold ui:text-brand-700! ui:underline! ui:underline-offset-4 ui:hover:text-ink!">
    {{ $label ?? $slot }}
</x-auth::elements.link>
