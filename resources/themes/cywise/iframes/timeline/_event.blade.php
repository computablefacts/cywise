<li id="eid-{{ $msg['id'] }}" class="timeline-item">
  <span class="timeline-item-hour">
    <span style="margin-left: -92px">{{ $time }}</span>
  </span>
  <span class="timeline-item-icon | faded-icon">
    <span class="bp4-icon bp4-icon-flow-linear"></span>
  </span>
  <div class="timeline-item-description">
      <span>
        <b>{{ $msg['server'] }} ({{ $msg['ip'] }})</b> - {{ $msg['message'] }}
        (<a href="#" onclick="dismissEvent('{{ $msg['id'] }}')">{{ __('dismiss') }}</a>)
      </span>
  </div>
</li>