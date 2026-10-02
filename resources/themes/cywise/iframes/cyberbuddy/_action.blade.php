{{-- Suggestion card: clicking it pre-fills the chat input. --}}
<button type="button" onclick="onActionClick('{{ e($text) }}')"
        class="ui:group ui:flex ui:flex-col ui:items-start ui:gap-3 ui:rounded-xl ui:border ui:border-solid ui:border-line ui:bg-white ui:p-4 ui:text-left ui:cursor-pointer ui:transition ui:hover:border-brand-200 ui:hover:shadow-md">
  <span class="ui:flex ui:items-center ui:gap-3">
    <span class="ui:flex ui:size-9 ui:shrink-0 ui:items-center ui:justify-center ui:rounded-lg ui:bg-brand-50 ui:text-brand-500">
      <x-dynamic-component :component="'phosphor-' . ($icon ?? 'chat-circle')" class="ui:size-5"/>
    </span>
    <span class="ui:flex ui:flex-col">
      <span class="ui:text-sm ui:font-semibold ui:text-ink">{{ $title }}</span>
      <span class="ui:text-xs ui:text-slate-500">{{ $subtitle }}</span>
    </span>
  </span>
  <span class="ui:text-sm ui:text-slate-600 ui:group-hover:text-ink">« {{ $text }} »</span>
</button>
@once
@push('scripts')
<script>

  const onActionClick = (text) => {

    const textarea = document.createElement('textarea');
    textarea.innerHTML = text;
    text = textarea.value;

    const elInputField = document.querySelector('.tw-chat-footer-input');
    elInputField.value = text;
    elInputField.focus();
  };

</script>
@endpush
@endonce
