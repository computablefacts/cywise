@extends('theme::iframes.app')

@push('styles')
<style>

  .timeline {
    width: 85%;
    max-width: 100%;
    margin-left: 80px;
    margin-right: auto;
    display: flex;
    flex-direction: column;
    padding: 32px 0 32px 32px;
    border-left: 2px solid var(--c-grey-200);
    font-size: 1rem;
    margin-bottom: 0;
  }

  .timeline-item {
    display: flex;
    gap: 24px;
  }

  .timeline-item + * {
    margin-top: 24px;
  }

  .timeline-item + .extra-space {
    margin-top: 48px;
  }

  .new-comment {
    width: 100%;
  }

  .new-comment input:not([type=checkbox]):not([type=radio]) {
    border: 1px solid var(--c-grey-200);
    border-radius: 6px;
    height: 48px;
    padding: 0 16px;
    width: 100%;
  }

  .new-comment input:not([type=checkbox]):not([type=radio])::-moz-placeholder {
    color: var(--c-grey-300);
  }

  .new-comment input:not([type=checkbox]):not([type=radio]):-ms-input-placeholder {
    color: var(--c-grey-300);
  }

  .new-comment input:not([type=checkbox]):not([type=radio])::placeholder {
    color: var(--c-grey-300);
  }

  .new-comment input:not([type=checkbox]):not([type=radio]):focus {
    border-color: var(--c-grey-300);
    outline: 0;
    box-shadow: 0 0 0 4px var(--c-grey-100);
  }

  .timeline-item-hour {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    margin-left: -65px;
    flex-shrink: 0;
    color: var(--c-grey-400);
  }

  .timeline-item-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    margin-left: -52px;
    flex-shrink: 0;
    overflow: hidden;
    box-shadow: 0 0 0 6px #fff;
  }

  .timeline-item-icon svg {
    width: 20px;
    height: 20px;
  }

  .timeline-item-icon.faded-icon {
    background-color: var(--c-grey-100);
    color: var(--c-grey-400);
  }

  .timeline-item-icon.filled-icon {
    background-color: var(--c-blue);
    color: #fff;
  }

  .timeline-item-wrapper {
    width: 100%;
  }

  .timeline-item-description {
    display: flex;
    gap: 8px;
    color: var(--c-grey-400);
    align-items: center;
  }

  .timeline-item-description img {
    flex-shrink: 0;
  }

  .timeline-item-description b {
    color: var(--c-grey-500);
    font-weight: 500;
    text-decoration: none;
  }

  .avatar {
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    overflow: hidden;
    aspect-ratio: 1/1;
    flex-shrink: 0;
    width: 40px;
    height: 40px;
  }

  .avatar.small {
    width: 28px;
    height: 28px;
  }

  .avatar img {
    -o-object-fit: cover;
    object-fit: cover;
  }

  .comment {
    margin-top: 12px;
    color: var(--c-grey-500);
    border: 1px solid var(--c-grey-200);
    box-shadow: 0 4px 4px 0 var(--c-grey-100);
    border-radius: 6px;
    padding: 16px;
    font-size: 0.8rem;
  }

  .button {
    border: 0;
    display: inline-flex;
    vertical-align: middle;
    margin-right: 4px;
    margin-top: 12px;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    height: 32px;
    padding: 0 8px;
    background-color: var(--c-grey-100);
    flex-shrink: 0;
    cursor: pointer;
    border-radius: 99em;
  }

  .button:hover {
    background-color: var(--c-grey-200);
  }

  .button.square {
    border-radius: 50%;
    color: var(--c-grey-400);
    f
    width: 32px;
    height: 32px;
    padding: 0;
  }

  .button.square svg {
    width: 24px;
    height: 24px;
  }

  .button.square:hover {
    background-color: var(--c-grey-200);
    color: var(--c-grey-500);
  }

  .show-replies {
    color: var(--c-grey-300);
    background-color: transparent;
    border: 0;
    padding: 0;
    margin-top: 16px;
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 1rem;
    cursor: pointer;
  }

  .show-replies svg {
    flex-shrink: 0;
    width: 24px;
    height: 24px;
  }

  .show-replies:hover, .show-replies:focus {
    color: var(--c-grey-500);
  }

  .avatar-list {
    display: flex;
    align-items: center;
  }

  .avatar-list > * {
    position: relative;
    box-shadow: 0 0 0 2px #fff;
    margin-right: -8px;
  }

  /* TABLE */

  .timeline-item-wrapper table {
    border-collapse: collapse;
    caption-side: bottom;
    display: table;
    width: 100%;
    font-size: 0.8rem;
    margin-top: 0;
  }

  .timeline-item-wrapper table thead {
    border-top-width: 1px;
    display: table-header-group;
    font-weight: 500;
    border-color: rgb(226, 232, 240);
    border-style: solid;
  }

  .timeline-item-wrapper table tr {
    border-bottom-width: 1px;
    display: table-row;
    border-color: rgb(226, 232, 240);
    border-style: solid;
  }

  .timeline-item-wrapper table tbody {
    display: table-row-group
  }

  .timeline-item-wrapper table thead tr th {
    padding: 0.5rem;
    vertical-align: middle;
    display: table-cell;
    height: 2rem;
  }

  .timeline-item-wrapper table tbody tr td {
    padding: 0.5rem;
    vertical-align: middle;
    display: table-cell;
  }

  /* SCROLL TO TOP */

  .scroll-to-top {
    position: fixed;
    top: calc(56px + 20px);
    right: 20px;
    background-color: var(--c-blue);
    color: white;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    border: none;
    cursor: pointer;
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    transition: all 0.3s ease;
  }

  .pre-light {
    color: #565656;
    padding: 0.5rem;
    background-color: #fff3cd;
  }

  .scroll-to-top:hover {
    background-color: var(--c-grey-500);
    transform: translateY(-1px);
  }

  .scroll-to-top.show {
    display: flex;
  }

</style>
@endpush

@section('content')
@if(request()->routeIs('iframes.assets'))
@include('theme::iframes.timeline._asset-counters')
<div class="row mt-3 mb-1">
  <div class="col">
    <div class="card">
      <div class="card-body p-3">
        <form method="get" action="{{ route('iframes.assets') }}" class="row g-2 align-items-end">
          <div class="col-sm-3">
            <label for="tld" class="form-label">
              {{ __('Domain') }}
            </label>
            <input type="text"
                   id="tld"
                   name="tld"
                   value="{{ request('tld') }}"
                   class="form-control"
                   placeholder="example.com">
          </div>
          <div class="col-sm-5">
            <label for="tags" class="form-label">
              {{ __('Tag') }}
            </label>
            <select id="tags" name="tags" class="form-select">
              <option value="">{{ __('All tags') }}</option>
              @foreach($tags as $tag)
              <option value="{{ $tag }}" {{ request(
              'tags') === $tag ? 'selected' : '' }}>
              {{ $tag }}
              </option>
              @endforeach
            </select>
          </div>
          <div class="col-sm-2">
            <label class="form-label d-block">&nbsp;</label>
            <button type="submit" class="btn btn-primary w-100">
              {{ __('Filter!') }}
            </button>
          </div>
          <div class="col-sm-2">
            <label class="form-label d-block">&nbsp;</label>
            <a href="{{ route('iframes.assets') }}" class="btn btn-secondary w-100">
              {{ __('Reset') }}
            </a>
          </div>
          @if(request('status'))
          <input type="hidden" name="status" value="{{ request('status') }}">
          @endif
          @if(request('asset_id'))
          <input type="hidden" name="asset_id" value="{{ request('asset_id') }}">
          @endif
        </form>
      </div>
    </div>
  </div>
</div>
@endif
@if(request()->routeIs('iframes.conversations'))
@include('theme::iframes.timeline._conversation-counters')
@endif
@if(request()->routeIs('iframes.events'))
@include('theme::iframes.timeline._event-counters')
<div class="row mt-3 mb-1">
  <div class="col">
    <div class="card">
      <div class="card-body p-3">
        <form method="get" action="{{ route('iframes.events') }}" class="row g-2 align-items-end">
          <div class="col-sm-8">
            <label for="rule_name" class="form-label">
              {{ __('Rule') }}
            </label>
            <select id="rule_name" name="rule_name" class="form-select">
              <option value="">{{ __('All rules') }}</option>
              @foreach($rules as $rule)
              <option value="{{ $rule->name }}" {{ request(
              'rule_name') === $rule->name ? 'selected' : '' }}>
              {{ $rule->displayName() }}
              </option>
              @endforeach
            </select>
          </div>
          <div class="col-sm-2">
            <label class="form-label d-block">&nbsp;</label>
            <button type="submit" class="btn btn-primary w-100">
              {{ __('Filter!') }}
            </button>
          </div>
          <div class="col-sm-2">
            <label class="form-label d-block">&nbsp;</label>
            <a href="{{ route('iframes.events') }}" class="btn btn-secondary w-100">
              {{ __('Reset') }}
            </a>
          </div>
          @if(request('server_id'))
          <input type="hidden" name="server_id" value="{{ request('server_id') }}">
          @endif
        </form>
      </div>
    </div>
  </div>
</div>
@endif
@if(request()->routeIs('iframes.ioc'))
@include('theme::iframes.timeline._ioc-counters')
<div class="row mt-3 mb-1">
  <div class="col">
    <div class="card">
      <div class="card-body p-3">
        <form method="get" action="{{ route('iframes.ioc') }}" class="row g-2 align-items-end">
          <div class="col-sm-8">
            <label for="rule_name" class="form-label">
              {{ __('Rule') }}
            </label>
            <select id="rule_name" name="rule_name" class="form-select">
              <option value="">{{ __('All rules') }}</option>
              @foreach($rules as $rule)
              <option value="{{ $rule->name }}" {{ request(
              'rule_name') === $rule->name ? 'selected' : '' }}>
              {{ $rule->displayName() }}
              </option>
              @endforeach
            </select>
          </div>
          <div class="col-sm-2">
            <label class="form-label d-block">&nbsp;</label>
            <button type="submit" class="btn btn-primary w-100">
              {{ __('Filter!') }}
            </button>
          </div>
          <div class="col-sm-2">
            <label class="form-label d-block">&nbsp;</label>
            <a href="{{ route('iframes.ioc') }}" class="btn btn-secondary w-100">
              {{ __('Reset') }}
            </a>
          </div>
          @if(request('level'))
          <input type="hidden" name="level" value="{{ request('level') }}">
          @endif
          @if(request('server_id'))
          <input type="hidden" name="server_id" value="{{ request('server_id') }}">
          @endif
        </form>
      </div>
    </div>
  </div>
</div>
@endif
@if(request()->routeIs('iframes.leaks'))
@include('theme::iframes.timeline._leak-counters')
@endif
@if(request()->routeIs('iframes.notes-and-memos'))
@include('theme::iframes.timeline._note-counters')
@endif
@if(request()->routeIs('iframes.vulnerabilities'))
@include('theme::iframes.timeline._vulnerability-counters')
<div class="row mt-3 mb-1">
  <div class="col">
    <div class="card">
      <div class="card-body p-3">
        <form method="get" action="{{ route('iframes.vulnerabilities') }}" class="row g-2 align-items-end">
          <div class="col-sm-3">
            <label for="tld" class="form-label">
              {{ __('Domain') }}
            </label>
            <input type="text"
                   id="tld"
                   name="tld"
                   value="{{ request('tld') }}"
                   class="form-control"
                   placeholder="example.com">
          </div>
          <div class="col-sm-3">
            <label for="tags" class="form-label">
              {{ __('User tag') }}
            </label>
            <select id="tags" name="tags" class="form-select">
              <option value="">{{ __('All tags') }}</option>
              @foreach($tags as $tag)
              <option value="{{ $tag }}" {{ request(
              'tags') === $tag ? 'selected' : '' }}>
              {{ $tag }}
              </option>
              @endforeach
            </select>
          </div>
          <div class="col-sm-3">
            <label for="port_tags" class="form-label">
              {{ __('System tag') }}
            </label>
            <select id="port_tags" name="port_tags" class="form-select">
              <option value="">{{ __('All tags') }}</option>
              @foreach($port_tags as $tag)
              <option value="{{ $tag }}" {{ request(
              'port_tags') === $tag ? 'selected' : '' }}>
              {{ $tag }}
              </option>
              @endforeach
            </select>
          </div>
          <div class="col-sm-1">
            <label class="form-label d-block">&nbsp;</label>
            <button type="submit" class="btn btn-primary w-100">
              {{ __('Filter!') }}
            </button>
          </div>
          <div class="col-sm-2">
            <label class="form-label d-block">&nbsp;</label>
            <a href="{{ route('iframes.vulnerabilities') }}" class="btn btn-secondary w-100">
              {{ __('Reset') }}
            </a>
          </div>
          @if(request('level'))
          <input type="hidden" name="level" value="{{ request('level') }}">
          @endif
          @if(request('asset_id'))
          <input type="hidden" name="asset_id" value="{{ request('asset_id') }}">
          @endif
        </form>
      </div>
    </div>
  </div>
</div>
@endif
@if(isset($selectedRule))
<div id="selected-rule-card" class="row mt-3 mb-1">
  <div class="col">
    <div class="card">
      <div class="card-header pb-0">
        <div class="row mt-2">
          <div class="col">
            <h6 id="rule-title">
              @if(isset($selectedRule->created_by) || \Auth::user()?->isCywiseAdmin())
              <a href="{{ route('iframes.rules-editor', ['rule_id' => $selectedRule->id]) }}">
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
              @if(\Illuminate\Support\Str::startsWith($selectedRule->comments, 'Needs further work on the collected data
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
                  <label class="p-2" for="scopeCyberBuddy">CyberBuddy</label>
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
@include('theme::iframes.timeline._share-modal')
@endsection

@push('scripts')
<script>

  /* HELPERS */

  const apiCall = (method, url, params = {}, body = null) => {

    let fullUrl = "{{ app_url() }}/api" + url;

    if (method.toUpperCase() === "GET" && Object.keys(params).length > 0) {
      const queryParams = new URLSearchParams(params).toString();
      fullUrl += "?" + queryParams;
    }

    const headers = {
      'Content-Type': 'application/json', 'Authorization': 'Bearer {{ Auth::user()->sentinelApiToken() }}',
    };

    const options = {
      method: method, headers: headers, body: body ? JSON.stringify(body) : null,
    };

    return fetch(fullUrl, options).catch(error => {
      toaster.toastError(error);
      console.error(error);
    });
  }

  const todaySeparatorHtmlTemplate = '{!! $today_separator !!}';

  const today = (() => {
    const date = new Date();
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
  })();

  /* SCROLL TO TOP */

  const elScrollBtn = document.getElementById("scrollToTopBtn");

  window.onscroll = () => {
    if (document.body.scrollTop > (56 + 20) || document.documentElement.scrollTop > (56 + 20)) {
      elScrollBtn.classList.add("show");
    } else {
      elScrollBtn.classList.remove("show");
    }
  };

  elScrollBtn.onclick = () => {
    document.body.scrollTop = 0;
    document.documentElement.scrollTop = 0;
  };

  /* NOTES */

  const elInputField = document.querySelector('.new-comment input');
  elInputField.addEventListener('keydown', (event) => {
    if (event.key === 'Enter') {

      event.preventDefault();

      if (elInputField.value.trim() !== '') {

        const scopes = Array.from(document.querySelectorAll('.note-scope:checked')).map(cb => cb.value);

        if (scopes.length === 0) {
          toaster.toastError("{{ __('Please select at least one scope.') }}");
          return;
        }

        createNoteApiCall(elInputField.value.trim(), scopes, (response) => {

          elInputField.value = null;
          const elNote = (new DOMParser()).parseFromString(response.html, 'text/html');
          let elTodaySeparator = document.querySelector(`#sid-${today}`);

          if (elTodaySeparator) {
            const elOl = elTodaySeparator.nextElementSibling;
            elOl.insertBefore(elNote.body.firstChild, elOl.firstElementChild);
          } else {

            elTodaySeparator = (new DOMParser()).parseFromString(todaySeparatorHtmlTemplate, 'text/html');
            const elTimelines = document.querySelectorAll(`.timeline`);

            if (elTimelines.length >= 1) {

              // Insert OL
              const elOl = document.createElement('ol')
              elOl.classList.add('timeline');
              elTimelines[0].parentNode.insertBefore(elOl, elTimelines[0].nextElementSibling);

              // Insert separator
              elTimelines[0].parentNode.insertBefore(elTodaySeparator.body.firstChild,
                elTimelines[0].nextElementSibling);

              // Fill OL with note
              elOl.appendChild(elNote.body.firstChild);
            }
          }
          return response;
        });
      }
    }
  });

  const deleteNote = (noteId) => {
    deleteNoteApiCall(noteId, (response) => {
      const elNote = document.querySelector(`#nid-${noteId}`);
      if (elNote) {
        elNote.remove();
      }
      return response;
    });
  }

  /** CONVERSATIONS */

  const deleteConversation = (conversationId) => {
    const response = confirm("{{ __('Are you sure you want to delete this conversation?') }}");
    if (response) {
      deleteConversationApiCall(conversationId, (response) => toaster.toastSuccess(response.msg));
    }
  }

  /* EVENTS */

  const dismissEvent = (eventId) => dismissEventApiCall(eventId, () => toaster.toastSuccess("{{ __('Hide events like this for this server in the timeline.') }}"));

  /* VULNERABILITIES */

  const hideByUid = (uid) => toggleVulnerabilityVisibilityApiCall(uid, null, null);
  const hideByType = (type) => toggleVulnerabilityVisibilityApiCall(null, type, null);
  const hideByTitle = (title) => toggleVulnerabilityVisibilityApiCall(null, null, title);
  const startMonitoringAsset = (assetId) => monitorAssetApiCall(assetId,
    () => {
      document.querySelectorAll(`#start-monitoring-${assetId}`).forEach(el => el.classList.add('d-none'));
      document.querySelectorAll(`#stop-monitoring-${assetId}`).forEach(el => el.classList.remove('d-none'));
      document.querySelectorAll(`#restart-scan-${assetId}`).forEach(el => el.classList.remove('d-none'));
      document.querySelectorAll(`#delete-asset-${assetId}`).forEach(el => el.classList.add('d-none'));
      toaster.toastSuccess("{{ __('The monitoring started.') }}");
    });
  const stopMonitoringAsset = (assetId) => unmonitorAssetApiCall(assetId,
    () => {
      document.querySelectorAll(`#start-monitoring-${assetId}`).forEach(el => el.classList.remove('d-none'));
      document.querySelectorAll(`#stop-monitoring-${assetId}`).forEach(el => el.classList.add('d-none'));
      document.querySelectorAll(`#restart-scan-${assetId}`).forEach(el => el.classList.add('d-none'));
      document.querySelectorAll(`#delete-asset-${assetId}`).forEach(el => el.classList.remove('d-none'));
      toaster.toastSuccess("{{ __('The monitoring stopped.') }}");
    });
  const deleteAsset = (assetId) => deleteAssetApiCall(assetId,
    () => toaster.toastSuccess("{{ __('The asset will be deleted soon.') }}"));
  const restartScan = (assetId) => restartAssetScanApiCall(assetId,
    () => toaster.toastSuccess("{{ __('The scan has been restarted.') }}"));

  /* ASSETS TAGGING */

  const addTagToAsset = (assetId) => {

    const input = document.getElementById(`tag-input-${assetId}`);
    if (!input) {
      return;
    }

    const value = (input.value || '').trim();
    if (value.length === 0) {
      return;
    }

    tagAssetApiCall(assetId, value, (response) => {

      input.value = '';

      if (!response || !response.tag) {
        return;
      }

      const tag = response.tag; // {id, tag}

      // If already present, do nothing
      if (document.getElementById(`tag-${tag.id}`)) {
        toaster.toastSuccess("{{ __('Tag already present.') }}");
        return;
      }

      const list = document.getElementById(`tags-${assetId}`);
      if (!list) {
        return;
      }

      const wrapper = document.createElement('span');
      wrapper.id = `tag-${tag.id}`;
      wrapper.className = 'lozenge new d-inline-flex align-items-center';

      const label = document.createElement('span');
      label.textContent = tag.tag;

      const btn = document.createElement('button');
      btn.type = 'button';
      btn.title = "{{ __('Remove tag') }}";
      btn.className = 'bp4-button bp4-minimal border-0 bg-transparent cursor-pointer';
      btn.style.minHeight = '15px';
      btn.style.maxWidth = '15px';

      btn.onclick = () => removeTagFromAsset(String(assetId), String(tag.id));

      const icon = document.createElement('span');
      icon.className = 'bp4-icon bp4-icon-cross';
      btn.appendChild(icon);

      wrapper.appendChild(label);
      wrapper.appendChild(btn);
      list.appendChild(wrapper);

      if (toaster) {
        toaster.toastSuccess("{{ __('Tag added.') }}");
      }
      toggleTagInput(assetId);
    });
  }

  const removeTagFromAsset = (assetId, tagId) => {
    untagAssetApiCall(assetId, tagId, (response) => {
      const elTag = document.getElementById(`tag-${tagId}`);
      if (elTag) {
        elTag.remove();
      }
      if (toaster) {
        const msg = response && response.msg ? response.msg : "{{ __('Tag removed.') }}";
        toaster.toastSuccess(msg);
      }
      toggleTagInput(assetId);
    });
  }

  const toggleTagInput = (assetId) => {
    const elTags = document.getElementById(`tags-${assetId}`);
    const elAddTag = document.getElementById(`add-tag-${assetId}`);
    if (elTags && elAddTag && elTags.childElementCount <= 5) {
      elAddTag.classList.remove('d-none');
    } else {
      elAddTag.classList.add('d-none');
    }
  };

  /* RULES DYNAMIC DISPLAY */

  const rulesDetails = @json($rulesDetails);

  const updateRuleDisplay = (ruleName) => {

    const elCard = document.getElementById('selected-rule-card');

    if (!elCard) {
      return;
    }
    if (!ruleName || !rulesDetails[ruleName]) {
      elCard.style.display = 'none';
      return;
    }

    const data = rulesDetails[ruleName];
    elCard.style.display = 'flex';

    // Title
    const elTitle = elCard.querySelector('#rule-title');
    if (data.can_edit) {
      elTitle.innerHTML = `<a href="${data.editor_url}">${data.display_name}</a>`;
    } else {
      elTitle.textContent = data.display_name;
    }

    // Tactics
    const elTactics = elCard.querySelector('#rule-tactics');
    elTactics.innerHTML = '';
    (data.tactics || []).forEach(tactic => {
      const span = document.createElement('span');
      span.className = 'lozenge new';
      span.textContent = tactic;
      elTactics.appendChild(span);
      elTactics.appendChild(document.createTextNode('\u00A0'));
    });

    // Description
    elCard.querySelector('#rule-description').textContent = data.description;

    // Platform
    elCard.querySelector('#rule-platform').textContent = data.platform;
    elCard.querySelector('#rule-interval').textContent = data.interval;

    // IoC & Score
    const elIocInfo = elCard.querySelector('#rule-ioc-info');
    let iocHtml = '';

    if (data.is_ioc) {
      iocHtml += `<span class="lozenge error">{{ __('yes') }}</span>&nbsp;`;
    } else {
      iocHtml += `<span class="lozenge success">{{ __('no') }}</span>&nbsp;`;
    }

    let scoreClass = 'neutral';

    if (data.score >= 75) {
      scoreClass = 'error';
    } else if (data.score >= 50) {
      scoreClass = 'warning';
    } else if (data.score >= 25) {
      scoreClass = 'information';
    }

    iocHtml += `<span class="lozenge ${scoreClass}">${data.score}&nbsp;/&nbsp;100</span>`;
    elIocInfo.innerHTML = iocHtml;

    // Mitre
    const elMitreRow = elCard.querySelector('#rule-mitre-row');
    const elMitreLinks = elCard.querySelector('#rule-mitre-links');

    if (data.mitre && data.mitre.length > 0) {
      elMitreRow.style.display = 'flex';
      elMitreLinks.innerHTML = '';
      data.mitre.forEach(m => {
        const a = document.createElement('a');
        a.href = m.url;
        a.target = '_blank';
        a.textContent = m.uid;
        elMitreLinks.appendChild(a);
        elMitreLinks.appendChild(document.createTextNode('\u00A0'));
      });
    } else {
      elMitreRow.style.display = 'none';
    }

    // Query
    elCard.querySelector('#rule-query').textContent = data.query;
  };

  document.querySelectorAll('select[name="rule_name"]').forEach(select => {
    select.addEventListener('change', (e) => {
      updateRuleDisplay(e.target.value);
    });
  });

</script>
@endpush

