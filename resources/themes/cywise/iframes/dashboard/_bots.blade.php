{{-- Messaging bots setup: one guide per messenger (iframes/_telegram, iframes/_whatsapp). --}}
<div x-data="{ bot: 'telegram' }" class="ui:flex ui:flex-col ui:gap-4">
  <div class="ui:inline-flex ui:self-start ui:rounded-lg ui:bg-slate-100 ui:p-1" role="tablist">
    @foreach(['telegram' => 'Telegram', 'whatsapp' => 'WhatsApp'] as $key => $label)
      <button type="button" role="tab" @click="bot = '{{ $key }}'" :aria-selected="bot === '{{ $key }}'"
              :class="bot === '{{ $key }}' ? 'ui:bg-white ui:text-ink ui:shadow-xs' : 'ui:bg-transparent ui:text-slate-500 ui:hover:text-ink'"
              class="ui:rounded-md ui:border-0 ui:px-3 ui:py-1.5 ui:text-sm ui:font-medium ui:cursor-pointer">
        {{ $label }}
      </button>
    @endforeach
  </div>
  <div x-show="bot === 'telegram'" class="ui:text-sm">
    @include('theme::iframes._telegram')
  </div>
  <div x-show="bot === 'whatsapp'" x-cloak class="ui:text-sm">
    @include('theme::iframes._whatsapp')
  </div>
</div>
