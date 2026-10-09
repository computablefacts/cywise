{{-- Read-only value (email at the password step, invitation email). --}}
@props([
    'value' => ''
])

<div data-auth="email-read-only-placeholder" {{ $attributes->merge(['class' => 'ui:flex ui:h-12 ui:items-center ui:justify-between ui:gap-3 ui:rounded-xl ui:border ui:border-solid ui:border-line ui:bg-slate-50 ui:px-4 ui:text-[15px]']) }}>
    <span class="ui:min-w-0 ui:truncate">{{ $value }}</span>
    {{ $slot }}
</div>
