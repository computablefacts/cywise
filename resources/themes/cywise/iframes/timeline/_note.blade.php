<li id="nid-{{ $note->id }}" class="timeline-item">
  <span class="timeline-item-hour">
    <span style="margin-left: -92px">{{ $time }}</span>
  </span>
  <span class="timeline-item-icon | faded-icon">
    <span class="bp4-icon bp4-icon-manually-entered-data"></span>
  </span>
  <div class="timeline-item-wrapper">
    <div class="timeline-item-description">
      <span>
        {!! __('<b>:user</b> created a <b>note</b>', [ 'user' => $user->name ]) !!}
      </span>
    </div>
    <div class="comment">
      @php
      $attributes = $note->attributes()
      @endphp
      @if(!empty($attributes['subject']))
      <b>{{ $attributes['subject'] ?? '' }}</b>
      <br><br>
      @endif
      {!! (new Parsedown)->text($attributes['body'] ?? '') !!}
    </div>
    <div style="display: flex; gap: 10px;">
      <button class="show-replies" title="{{ __('Delete') }}" onclick="deleteNote('{{ $note->id }}')">
        <span class="bp4-icon bp4-icon-trash"></span>
      </button>
    </div>
  </div>
</li>