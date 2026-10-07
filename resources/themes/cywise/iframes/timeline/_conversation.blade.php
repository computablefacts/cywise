{{--
  Conversation row: [icon] [author / description] [date] [delete].
  Delete only on conversations with a description (as before).
--}}
@php
  $isLinked = empty($conversation->description) || $conversation->format === \App\Models\Conversation::FORMAT_V1;
@endphp

{{-- data-search: date, searchable (x-ui.search) --}}
<li id="cid-{{ $conversation->id }}" data-search="{{ $date }}"
    class="ui:flex ui:items-start ui:gap-4 ui:border-0 ui:border-t ui:border-solid ui:border-line ui:px-5 ui:py-3">
  <span class="ui:flex ui:size-8 ui:shrink-0 ui:items-center ui:justify-center ui:rounded-full ui:bg-brand-50 ui:text-brand-500">
    <x-phosphor-chat-circle class="ui:size-4"/>
  </span>
  <div class="ui:flex ui:min-w-0 ui:flex-1 ui:flex-col ui:gap-1 ui:pt-1.5">
    <span class="ui:text-xs ui:text-slate-500 ui:[&_a]:text-brand-600! ui:[&_a]:no-underline! ui:[&_a]:hover:underline!">
      @if($isLinked)
        {!! __('<b>:user</b> started a <a href=":href" class="link">conversation</a>', [
          'user' => $conversation->createdBy->name,
          'href' => route('cyberbuddy', [ 'conversation_id' => $conversation->id ]) ])
        !!}
      @else
        {!! __('<b>:user</b> started a <b>conversation</b>', [ 'user' => $conversation->createdBy->name ]) !!}
      @endif
    </span>
    @if(!empty($conversation->description))
      <p class="ui:m-0 ui:text-sm ui:text-ink">{{ $conversation->description }}</p>
    @endif
  </div>
  <span class="ui:hidden ui:w-28 ui:shrink-0 ui:pt-1.5 ui:text-right ui:text-xs ui:text-slate-400 ui:md:block">{{ $date }} {{ $time }}</span>
  @if(!empty($conversation->description))
    <x-ui.icon-button icon="trash" :title="__('Delete')" onclick="deleteConversation('{{ $conversation->id }}')"/>
  @else
    {{-- Keeps the date column aligned with deletable rows --}}
    <span class="ui:size-8 ui:shrink-0" aria-hidden="true"></span>
  @endif
</li>
