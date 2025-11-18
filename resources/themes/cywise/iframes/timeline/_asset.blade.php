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
    <div class="d-flex align-items-center flex-wrap" style="gap:10px;">
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
      <button class="show-replies" title="{{ __('Restart Scan') }}"
              onclick="restartScan('{{ $asset->id }}')">
        <span class="bp4-icon bp4-icon-repeat"></span>
      </button>
      @endif
      <div class="d-flex align-items-center" style="gap:6px;margin-top:16px;">
        @php( $tags = $asset->tags()->orderBy('tag')->get() )
        <div id="tags-{{ $asset->id }}">
          @foreach($tags as $t)
          <span id="tag-{{ $t->id }}" class="lozenge new d-inline-flex align-items-center">
            <span>{{ $t->tag }}</span>
            <button type="button" class="bp4-button bp4-minimal border-0 bg-transparent cursor-pointer"
                    title="{{ __('Remove tag') }}"
                    style="min-height: 15px;min-width: 15px;"
                    onclick="removeTagFromAsset('{{ $asset->id }}','{{ $t->id }}')">
              <span class="bp4-icon bp4-icon-cross"></span>
            </button>
          </span>
          @endforeach
        </div>
        <div id="add-tag-{{ $asset->id }}"
             class="add-tag d-inline-flex {{ $tags->count() > 5 ? 'd-none' : '' }}"
             style="gap:6px; align-items:center;">
          <input id="tag-input-{{ $asset->id }}"
                 type="text"
                 placeholder="{{ __('Add a tag') }}..."
                 maxlength="20"
                 onkeydown="if(event.key === 'Enter') { event.preventDefault(); addTagToAsset('{{ $asset->id }}'); }"
                 style="height:25px;padding:0 8px;border:1px solid var(--c-grey-200);border-radius:6px;">
          <button class="show-replies mt-0" title="{{ __('Add a tag') }}" onclick="addTagToAsset('{{ $asset->id }}')">
            <span class="bp4-icon bp4-icon-tag"></span>
          </button>
        </div>
      </div>
    </div>
  </div>
</li>