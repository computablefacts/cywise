<li id="cid-{{ $conversation->id }}" class="timeline-item">
  <span class="timeline-item-hour">
    <span style="margin-left: -92px">{{ $time }}</span>
  </span>
  <span class="timeline-item-icon | faded-icon">
    <span class="bp4-icon bp4-icon-chat"></span>
  </span>
  @if(empty($conversation->description))
  <div class="timeline-item-description">
      <span>
        {!! __('<b>:user</b> started a <a href=":href" class="link">conversation</a>', [
          'user' => $conversation->createdBy()->name,
          'href' => route('iframes.cyberbuddy', [ 'conversation_id' => $conversation->id ]) ])
        !!}
      </span>
  </div>
  @else
  <div class="timeline-item-wrapper">
    <div class="timeline-item-description">
      <span>
        @if($conversation->format === \App\Models\Conversation::FORMAT_V1)
        {!! __('<b>:user</b> started a <a href=":href" class="link">conversation</a>', [
          'user' => $conversation->createdBy()->name,
          'href' => route('iframes.cyberbuddy', [ 'conversation_id' => $conversation->id ]) ])
        !!}
        @else
        {!! __('<b>:user</b> started a <b>conversation</b>', [ 'user' => $conversation->createdBy()->name ]) !!}
        @endif
      </span>
    </div>
    <div class="comment">
      {{ $conversation->description }}
    </div>
    <div style="display: flex; gap: 10px;">
      <button class="show-replies" title="{{ __('Delete') }}" onclick="deleteConversation('{{ $conversation->id }}')">
        <span class="bp4-icon bp4-icon-trash"></span>
      </button>
    </div>
  </div>
  @endif
</li>