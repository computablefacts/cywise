<li id='aid-{{ $asset->id }}' class="timeline-item">
  <span class="timeline-item-hour">
    <span style="margin-left: -92px">{{ $time }}</span>
  </span>
  <span class="timeline-item-icon | faded-icon"
        style="background-color: var(--c-blue) !important; color: white !important;">
    <span class="bp4-icon bp4-icon-globe-network"></span>
  </span>
  <div class="timeline-item-wrapper">
    <div class="timeline-item-description">
      <span>
        @if($alerts->count() > 0)
        {!! __('<b>:user</b> has added the asset <b>:asset</b> (<a href=":href" class="link">:count vulnerabilities</a>)', [
        'asset' => $asset->asset,
        'count' => $alerts->count(),
        'href' => route('iframes.vulnerabilities', [ 'asset_id' => $asset->id ]),
        'user' => $asset->createdBy()->name
        ]) !!}
        @else
        {!! __('<b>:user</b> has added the asset <b>:asset</b>', [
        'asset' => $asset->asset,
        'user' => $asset->createdBy()->name
        ]) !!}
        @endif
      </span>
    </div>
    <div style="display: flex; gap: 10px;">
      @if(!$asset->is_monitored)
      <button class="show-replies" title="{{ __('Start Monitoring') }}"
              onclick="startMonitoringAsset('{{ $asset->id }}')">
        <span class="bp4-icon bp4-icon-play"></span>
      </button>
      <button class="show-replies" title="{{ __('Delete') }}" onclick="deleteAsset('{{ $asset->id }}')">
        <span class="bp4-icon bp4-icon-trash"></span>
      </button>
      @else
      <button class="show-replies" title="{{ __('Stop Monitoring') }}"
              onclick="stopMonitoringAsset('{{ $asset->id }}')">
        <span class="bp4-icon bp4-icon-symbol-square"></span>
      </button>
      @endif
    </div>
  </div>
</li>