@if($ioc['in_between'] >= 1)
<li id="eid-{{ $ioc['first']['ioc']->id }}" class="timeline-item">
  <span class="timeline-item-hour">
    <span style="margin-left: -92px">{{ $ioc['first']['time'] }}</span>
  </span>
  <span class="timeline-item-icon | faded-icon"
        style="background-color:{{ $ioc['first']['bgColor'] }} !important; color:{{ $ioc['first']['txtColor'] }} !important;">
    <span class="bp4-icon bp4-icon-search"></span>
  </span>
  <div class="timeline-item-wrapper">
    <div class="timeline-item-description">
      <span>
        <b>{{ $ioc['first']['ioc']->server_name }} ({{ $ioc['first']['ioc']->server_ip_address }})</b> - {{ $ioc['first']['ioc']->comments }} {{ $ioc['first']['level'] }}
      </span>
    </div>
    <pre class="comment mb-0">{{ json_encode($ioc['first']['ioc']->columns, JSON_PRETTY_PRINT) }}</pre>
  </div>
</li>
@endif
@if($ioc['in_between'] > 2)
<li class="timeline-item">
  <span class="timeline-item-hour">
    <span style="margin-left: -92px"></span>
  </span>
  <span class="timeline-item-icon | faded-icon">
    <span class="bp4-icon bp4-icon-more"></span>
  </span>
  <div class="timeline-item-description">
      <span>
        &plus;&nbsp;<b>{{ $ioc['in_between'] - 2 }}</b>&nbsp;{{ __('similar events') }}
      </span>
  </div>
</li>
@endif
@if($ioc['in_between'] >= 2)
<li id="eid-{{ $ioc['last']['ioc']->id }}" class="timeline-item">
  <span class="timeline-item-hour">
    <span style="margin-left: -92px">{{ $ioc['last']['time'] }}</span>
  </span>
  <span class="timeline-item-icon | faded-icon"
        style="background-color:{{ $ioc['last']['bgColor'] }} !important; color:{{ $ioc['last']['txtColor'] }} !important;">
    <span class="bp4-icon bp4-icon-search"></span>
  </span>
  <div class="timeline-item-wrapper">
    <div class="timeline-item-description">
      <span>
        <b>{{ $ioc['last']['ioc']->server_name }} ({{ $ioc['last']['ioc']->server_ip_address }})</b> - {{ $ioc['last']['ioc']->comments }} {{ $ioc['last']['level'] }}
      </span>
    </div>
    <pre class="comment mb-0">{{ json_encode($ioc['last']['ioc']->columns, JSON_PRETTY_PRINT) }}</pre>
  </div>
</li>
@endif