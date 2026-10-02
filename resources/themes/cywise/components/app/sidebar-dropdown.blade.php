<div x-data="{ {{ $id }}: {{ $open ?? false }} }" class="ui:w-full ui:select-none">
    <button type="button"
            @click="{{ $id }}=!{{ $id }}"
            :aria-expanded="{{ $id }}"
            class="ui:flex ui:w-full ui:items-center ui:gap-3 ui:rounded-lg ui:border-0 ui:bg-transparent ui:px-3 ui:py-2 ui:text-sm ui:text-slate-600 ui:hover:bg-slate-100 ui:hover:text-ink ui:cursor-pointer ui:transition-colors">
        <x-dynamic-component :component="$icon" class="ui:size-5 ui:shrink-0"/>
        <span class="ui:flex-1 ui:truncate ui:text-left">{{ $text }}</span>
        <x-phosphor-caret-down class="ui:size-4 ui:shrink-0 ui:text-slate-400 ui:transition-transform"
                               ::class="{ 'ui:rotate-180' : {{ $id }} }"/>
    </button>

    {{-- Children: indented under a guide line --}}
    <div x-show="{{ $id }}" x-collapse x-cloak>
        <div class="ui:ml-5 ui:mt-1 ui:flex ui:flex-col ui:gap-0.5 ui:border-0 ui:border-l ui:border-solid ui:border-line ui:pl-2">
            {{ $slot }}
        </div>
    </div>
</div>
