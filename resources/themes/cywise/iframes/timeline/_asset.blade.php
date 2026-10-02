{{--
  Asset row, condensed: one line. Clicking it shows the details in the page side panel.

    (globe) name  Scanner · Agent · added by …  [prod] [web] +2  [!]  N vulnerabilities  >

    #asset-panel (pages/assets)
    ┌──────────────────────────────┐
    │ name                      [x]│
    │ meta                         │
    │ N vulnerabilities >          │
    │ warnings                     │
    │ tags  [+ tag]                │
    │ [ ] auto-monitor subdomains  │
    │ [actions]                    │
    └──────────────────────────────┘

  Details are teleported into the panel: element ids (start-monitoring-*, tags-*, …) stay unique JS hooks
  (iframes/_scripts). Requires a parent x-data exposing `selected`.
--}}
@php
  $worst = match (true) {
    $alerts->contains(fn($a) => $a->isHigh()) => 'high',
    $alerts->contains(fn($a) => $a->isMedium()) => 'medium',
    $alerts->contains(fn($a) => $a->isLow()) => 'low',
    default => null,
  };
  $tags = $asset->tags()->orderBy('tag')->get();

  // Count color follows the worst severity (e.g. 1 high + 2 medium => red)
  $countColor = match ($worst) {
    'high' => 'ui:text-red-600',
    'medium' => 'ui:text-amber-700',
    'low' => 'ui:text-emerald-700',
    default => 'ui:text-slate-400',
  };

  // Monitoring sources, shown in the meta line instead of badges
  $sources = array_filter([
    $asset->is_monitored ? __('Scanner') : __('Not monitored'),
    $asset->hasAgent() ? __('Agent') : null,
  ]);

  // Row: a single warning icon (tooltip). Panel: full texts.
  $warnings = array_filter([
    $asset->isProtectedByCloudflare() ? __('This asset seems protected by Cloudflare. Do not forget to whitelist <a href="/ips-v4.txt" target="_blank">our IP addresses</a>.') : null,
    $asset->isIpAddressMissing() ? __('This asset is not associated with any IP address.') : null,
  ]);

  $meta = implode(' · ', $sources) . ' · ' . __('Added by :user on :date', ['user' => $asset->createdBy?->name ?? '-', 'date' => $date]);
  // Keys are prefixed: asset and server ids share the same `selected` state (pages/assets)
  $key = "asset-{$asset->id}";
  $isSelected = "selected === '{$key}'";

  // Row shows the first tags, then "+N"; the tag input hides past $maxTags.
  // Keep in sync with MAX_ROW_TAGS and MAX_ASSET_TAGS (iframes/_scripts).
  $maxRowTags = 2;
  $maxTags = 5;
@endphp

<li id="aid-{{ $asset->id }}"
    x-init="if (location.hash === '#aid-{{ $asset->id }}') { selected = '{{ $key }}'; openGroupOf($el) }"
    data-search="{{ $tags->pluck('tag')->implode(' ') }}" class="ui:border-0 ui:border-t ui:border-solid ui:border-line">

  {{-- Summary line (the vulnerability link must not select the row) --}}
  <div role="button" tabindex="0" @click="selected = '{{ $key }}'"
       @keydown.enter.prevent="selected = '{{ $key }}'" :aria-pressed="{{ $isSelected }}"
       :class="{{ $isSelected }} ? 'ui:bg-brand-50 ui:shadow-[inset_3px_0_0_var(--ui-color-brand-500)]' : 'ui:hover:bg-slate-50'"
       class="ui:flex ui:items-center ui:gap-3 ui:px-5 ui:py-2 ui:cursor-pointer ui:outline-none!">
    <x-phosphor-globe class="ui:size-4 ui:shrink-0 ui:text-slate-400"/>

    <span class="ui:flex ui:min-w-0 ui:flex-1 ui:flex-col ui:md:flex-row ui:md:items-baseline ui:md:gap-3">
      <span class="ui:truncate ui:text-sm ui:font-semibold ui:text-ink ui:md:max-w-[60%] ui:md:shrink-0">{{ $asset->asset }}</span>
      <span class="ui:truncate ui:text-xs ui:text-slate-500">{{ $meta }}</span>
    </span>

    {{-- Tags preview, e.g. [prod] [web] +2 (rebuilt by renderRowTags on tag change) --}}
    <span id="row-tags-{{ $asset->id }}" class="ui:hidden ui:shrink-0 ui:items-center ui:gap-1 ui:md:flex">
      @foreach($tags->take($maxRowTags) as $t)
        <x-ui.tag tone="auto">{{ $t->tag }}</x-ui.tag>
      @endforeach
      @if($tags->count() > $maxRowTags)
        <span class="ui:text-xs ui:font-medium ui:text-slate-500">+{{ $tags->count() - $maxRowTags }}</span>
      @endif
    </span>

    @if(!empty($warnings))
      <span title="{{ strip_tags(implode(' ', $warnings)) }}" class="ui:flex ui:shrink-0 ui:text-amber-600">
        <x-phosphor-warning class="ui:size-4"/>
      </span>
    @endif

    @if($alerts->count() > 0 || $asset->is_monitored)
      <a href="{{ route('vulnerabilities', ['asset_id' => $asset->id]) }}" @click.stop
         class="ui:flex ui:w-32 ui:shrink-0 ui:items-baseline ui:justify-end ui:gap-1.5 ui:no-underline!">
        <span class="ui:text-base ui:font-semibold ui:tabular-nums {{ $countColor }}">{{ $alerts->count() }}</span>
        <span class="ui:text-xs ui:text-slate-500">{{ trans_choice('vulnerability|vulnerabilities', $alerts->count()) }}</span>
      </a>
    @else
      <span class="ui:w-32 ui:shrink-0"></span>
    @endif

    <x-phosphor-caret-right class="ui:size-4 ui:shrink-0 ui:text-slate-400"/>
  </div>

  {{-- Details: header, warnings, tags, options, actions (rendered in the side panel) --}}
  <template x-teleport="#asset-panel">
    <div x-show="{{ $isSelected }}" x-cloak class="ui:flex ui:flex-col ui:gap-4 ui:p-5">

      <div class="ui:flex ui:items-start ui:gap-3">
        <div class="ui:flex ui:min-w-0 ui:flex-1 ui:flex-col ui:gap-0.5">
          <span class="ui:break-all ui:text-base ui:font-semibold ui:text-ink">{{ $asset->asset }}</span>
          <span class="ui:text-xs ui:text-slate-500">{{ $meta }}</span>
        </div>
        <x-ui.icon-button icon="x" :title="__('Close')" @click="selected = null"/>
      </div>

      @if($alerts->count() > 0 || $asset->is_monitored)
        <a href="{{ route('vulnerabilities', ['asset_id' => $asset->id]) }}"
           class="ui:flex ui:w-fit ui:items-baseline ui:gap-1.5 ui:no-underline!">
          <span class="ui:text-2xl ui:font-semibold ui:tabular-nums {{ $countColor }}">{{ $alerts->count() }}</span>
          <span class="ui:text-sm ui:text-slate-500">{{ trans_choice('vulnerability|vulnerabilities', $alerts->count()) }}</span>
          <x-phosphor-caret-right class="ui:size-3.5 ui:self-center ui:text-slate-400"/>
        </a>
      @endif

      @foreach($warnings as $warning)
        <span class="ui:flex ui:items-start ui:gap-1 ui:text-xs ui:text-amber-700">
          <x-phosphor-warning class="ui:size-3.5 ui:shrink-0"/>
          <span>{!! $warning !!}</span>
        </span>
      @endforeach

      <div class="ui:flex ui:flex-wrap ui:items-center ui:gap-1.5">
        <div id="tags-{{ $asset->id }}" class="ui:contents">
          @foreach($tags as $t)
            <x-ui.tag id="tag-{{ $t->id }}" tone="auto">
              {{ $t->tag }}
              <x-slot:remove>
                <button type="button" title="{{ __('Remove tag') }}"
                        onclick="removeTagFromAsset('{{ $asset->id }}','{{ $t->id }}')"
                        class="ui:flex ui:size-4 ui:items-center ui:justify-center ui:rounded ui:border-0 ui:bg-transparent ui:p-0 ui:text-slate-400 ui:cursor-pointer ui:hover:bg-slate-200 ui:hover:text-ink">&times;</button>
              </x-slot:remove>
            </x-ui.tag>
          @endforeach
        </div>
        <div id="add-tag-{{ $asset->id }}" class="ui:inline-flex ui:items-center {{ $tags->count() > $maxTags ? 'd-none' : '' }}">
          <input id="tag-input-{{ $asset->id }}" type="text" maxlength="20"
                 placeholder="+ {{ __('Add a tag') }}"
                 onkeydown="if(event.key === 'Enter') { event.preventDefault(); addTagToAsset('{{ $asset->id }}'); }"
                 class="ui:h-6 ui:w-36 ui:rounded-md ui:border ui:border-dashed ui:border-slate-300 ui:bg-transparent ui:px-2 ui:text-xs ui:text-ink ui:placeholder:text-slate-400 ui:focus:w-44 ui:focus:border-solid ui:focus:border-brand-500 ui:focus:bg-white ui:focus:outline-none ui:transition-all">
        </div>
      </div>

      @if($asset->isDns())
        {{-- Highlighted on a root domain (acme.example): its flag drives the subdomains discovered --}}
        <label class="ui:m-0 ui:flex ui:items-center ui:gap-2 ui:cursor-pointer {{ $asset->asset === $asset->tld() ? 'ui:rounded-lg ui:border ui:border-solid ui:border-brand-200 ui:bg-brand-50 ui:p-3 ui:text-sm ui:font-medium ui:text-ink' : 'ui:w-fit ui:text-xs ui:text-slate-600' }}">
          <input type="checkbox" id="auto-monitor-{{ $asset->id }}" class="ui:accent-brand-500"
                 {{ $asset->auto_monitor_new_subdomains ? 'checked' : '' }}
                 onclick="toggleAutoMonitorNewSubdomains('{{ $asset->id }}')">
          {{ __('Auto Monitor New Subdomains') }}
        </label>
      @endif

      <div class="ui:flex ui:items-center ui:gap-1.5 ui:border-0 ui:border-t ui:border-solid ui:border-line ui:pt-4">
        <x-ui.icon-button id="start-monitoring-{{ $asset->id }}" icon="play" :title="__('Start Monitoring')"
                          class="{{ $asset->is_monitored ? 'd-none' : '' }}" onclick="startMonitoringAsset('{{ $asset->id }}')"/>
        <x-ui.icon-button id="stop-monitoring-{{ $asset->id }}" icon="stop" :title="__('Stop Monitoring')"
                          class="{{ $asset->is_monitored ? '' : 'd-none' }}" onclick="stopMonitoringAsset('{{ $asset->id }}')"/>
        <x-ui.icon-button id="restart-scan-{{ $asset->id }}" icon="arrow-clockwise" :title="__('Restart Scan')"
                          class="{{ $asset->is_monitored ? '' : 'd-none' }}" onclick="restartScan('{{ $asset->id }}')"/>
        <x-ui.icon-button id="delete-asset-{{ $asset->id }}" icon="trash" :title="__('Delete')"
                          class="{{ $asset->is_monitored ? 'd-none' : '' }}" onclick="deleteAsset('{{ $asset->id }}')"/>
        <x-ui.icon-button id="share-asset-{{ $asset->id }}" icon="share-network" :title="__('Share')"
                          onclick="openShareModal('asset','{{ $asset->id }}')"/>
      </div>
    </div>
  </template>
</li>
