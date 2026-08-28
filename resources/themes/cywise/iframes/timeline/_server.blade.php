<li id='servid-{{ $server->id }}' class="timeline-item">
  <span class="timeline-item-hour">
    <span style="margin-left: -92px">{{ $time }}</span>
  </span>
  <span class="timeline-item-icon | faded-icon"
        style="background-color: var(--c-blue) !important; color: white !important;">
    <span class="bp4-icon bp4-icon-desktop"></span>
  </span>
  <div class="timeline-item-description">
      <span>
        @if($server->isMonitored())
          <span class="badge bg-secondary me-1" title="{{ __('External Monitoring (Scanner)') }}">
            <span class="bp4-icon bp4-icon-search"></span> {{ __('Scanner') }}
          </span>&nbsp;
        @endif
        @if($server->isReady())
          <span class="badge bg-secondary me-1" title="{{ __('Internal Monitoring (Agent)') }}">
            <span class="bp4-icon bp4-icon-pulse"></span> {{ __('Agent') }}
          </span>&nbsp;
        @endif
        {!! __('<b>:user</b> has added the server <b>:name</b> with IP address <b>:ip</b>', [
        'name' => $server->name,
        'ip' => $server->ip() ?? $server->ipv6(),
        'user' => $server->user?->name
        ]) !!}
      </span>
  </div>
</li>