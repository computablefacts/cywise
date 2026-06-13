@if(isset($selectedRule))
<div id="selected-rule-card" class="row mt-3 mb-1">
  <div class="col">
    <div class="card">
      <div class="card-header pb-0">
        <div class="row mt-2">
          <div class="col">
            <h6 id="rule-title">
              @if(isset($selectedRule->created_by) || \Auth::user()?->isCywiseAdmin())
              <a href="{{ route('rules-editor', ['rule_id' => $selectedRule->id]) }}">
                {{ $selectedRule->displayName() }}
              </a>
              @else
              {{ $selectedRule->displayName() }}
              @endif
            </h6>
          </div>
          <div class="col col-auto" id="rule-tactics">
            @if(!empty($selectedRule->mitreAttckTactics()))
            @foreach($selectedRule->mitreAttckTactics() as $tactic)
            <span class="lozenge new">{{ \Illuminate\Support\Str::lower($tactic) }}</span>&nbsp;
            @endforeach
            @endif
          </div>
        </div>
      </div>
      <div class="card-body pt-0">
        <div class="row mt-2">
          <div class="col col-2 text-end">
            <b>{{ __('Description') }}</b>
          </div>
          <div class="col">
            <div class="text-muted" id="rule-description">
              @if(\Illuminate\Support\Str::startsWith($selectedRule->comments, 'Needs further work on the collected
              data
              to be useful'))
              {{ $selectedRule->description }}
              @else
              {{ $selectedRule->comments }}
              @endif
            </div>
          </div>
        </div>
        <div class="row mt-2">
          <div class="col col-2 text-end">
            <b>{{ __('Platform') }}</b>
          </div>
          <div class="col" id="rule-platform-info">
        <span class="lozenge information" id="rule-platform">
          {{ $selectedRule->platform->value }}
        </span>&nbsp;
            <span class="lozenge information" id="rule-interval">
          {{ \Carbon\CarbonInterval::seconds($selectedRule->interval)->cascade()->forHumans() }}
        </span>
          </div>
        </div>
        <div class="row mt-2">
          <div class="col col-2 text-end">
            <b>{{ __('IoC') }}</b>
          </div>
          <div class="col" id="rule-ioc-info">
            @if($selectedRule->is_ioc)
            <span class="lozenge error">{{ __('yes') }}</span>&nbsp;
            @else
            <span class="lozenge success">{{ __('no') }}</span>&nbsp;
            @endif
            @if($selectedRule->score >= 75)
            <span class="lozenge error">{{ $selectedRule->score }}&nbsp;/&nbsp;100</span>
            @elseif($selectedRule->score >= 50)
            <span class="lozenge warning">{{ $selectedRule->score }}&nbsp;/&nbsp;100</span>
            @elseif($selectedRule->score >= 25)
            <span class="lozenge information">{{ $selectedRule->score }}&nbsp;/&nbsp;100</span>
            @else
            <span class="lozenge neutral">{{ $selectedRule->score }}&nbsp;/&nbsp;100</span>
            @endif
          </div>
        </div>
        <div id="rule-mitre-row" class="row mt-2" @if(empty($selectedRule->attck)) style="display: none;" @endif>
          <div class="col col-2 text-end">
            <b>{{ __('Mitre') }}</b>
          </div>
          <div class="col" id="rule-mitre-links">
            @if(!empty($selectedRule->attck))
            @foreach(explode(',', $selectedRule->attck) as $attck)
            @if(\Illuminate\Support\Str::startsWith($attck, 'TA'))
            <a href="https://attack.mitre.org/tactics/{{ $attck }}/" target="_blank">{{ $attck }}</a>&nbsp;
            @else
            <a href="https://attack.mitre.org/techniques/{{ $attck }}/" target="_blank">{{ $attck }}</a>&nbsp;
            @endif
            @endforeach
            @endif
          </div>
        </div>
        <div class="row mt-2">
          <div class="col col-2 text-end">
            <b>{{ __('Rule') }}</b>
          </div>
          <div class="col">
            <div style="display:grid;">
              <div class="overflow-auto">
                <pre id="rule-query" class="mb-0 w-100 pre-light">{{ $selectedRule->query }}</pre>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@else
<div id="selected-rule-card" class="row mt-3 mb-1" style="display: none;">
  <div class="col">
    <div class="card">
      <div class="card-header pb-0">
        <div class="row mt-2">
          <div class="col">
            <h6 id="rule-title"></h6>
          </div>
          <div class="col col-auto" id="rule-tactics"></div>
        </div>
      </div>
      <div class="card-body pt-0">
        <div class="row mt-2">
          <div class="col col-2 text-end">
            <b>{{ __('Description') }}</b>
          </div>
          <div class="col">
            <div class="text-muted" id="rule-description"></div>
          </div>
        </div>
        <div class="row mt-2">
          <div class="col col-2 text-end">
            <b>{{ __('Platform') }}</b>
          </div>
          <div class="col" id="rule-platform-info">
            <span class="lozenge information" id="rule-platform"></span>&nbsp;
            <span class="lozenge information" id="rule-interval"></span>
          </div>
        </div>
        <div class="row mt-2">
          <div class="col col-2 text-end">
            <b>{{ __('IoC') }}</b>
          </div>
          <div class="col" id="rule-ioc-info"></div>
        </div>
        <div id="rule-mitre-row" class="row mt-2" style="display: none;">
          <div class="col col-2 text-end">
            <b>{{ __('Mitre') }}</b>
          </div>
          <div class="col" id="rule-mitre-links"></div>
        </div>
        <div class="row mt-2">
          <div class="col col-2 text-end">
            <b>{{ __('Rule') }}</b>
          </div>
          <div class="col">
            <div style="display:grid;">
              <div class="overflow-auto">
                <pre id="rule-query" class="mb-0 w-100 pre-light"></pre>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endif
<div class="row mt-3 mb-3">
  <div class="col">
    <div class="card">
      <div class="card-body">
        <ol class="timeline">
          <li class="timeline-item">
            <span class="timeline-item-icon | filled-icon">
              <span class="bp4-icon bp4-icon-manually-entered-data"></span>
            </span>
            <div class="new-comment">
              <input type="text" placeholder="{{ __('Add a note... (press Enter to submit)') }}"/>
              <div class="mt-2 d-inline-flex align-items-center">
                <div class="d-inline-flex align-items-center">
                  <input class="note-scope" type="checkbox" id="scopeCyberBuddy" value="CyberBuddy" checked>
                  <label class="p-2" for="scopeCyberBuddy">{{ tenant_custom_text('CyberBuddy') }}</label>
                </div>
                <div class="d-inline-flex align-items-center">
                  <input class="note-scope" type="checkbox" id="scopeSOC" value="SOC Operator">
                  <label class="p-2" for="scopeSOC">SOC Operator</label>
                </div>
                <div class="d-inline-flex align-items-center">
                  <input class="mr-2 note-scope" type="checkbox" id="scopeOrchestrator" value="Orchestrator">
                  <label class="p-2" for="scopeOrchestrator">Orchestrator</label>
                </div>
              </div>
            </div>
          </li>
        </ol>
        @foreach($items as $date => $times)
        @if(empty($dateId) || $date === $dateId)
        @include('theme::iframes.timeline._separator', ['date' => $date])
        <ol class="timeline">
          @foreach($times as $time => $events)
          @foreach($events as $event)
          {!! $event['html'] !!}
          @endforeach
          @endforeach
        </ol>
        @endif
        @endforeach
      </div>
    </div>
  </div>
  <button id="scrollToTopBtn" class="scroll-to-top" title="Go to top">
    <span class="bp4-icon bp4-icon-arrow-up"></span>
  </button>
</div>