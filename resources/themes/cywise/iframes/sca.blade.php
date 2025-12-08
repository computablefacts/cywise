@extends('theme::iframes.app')

@push('styles')
<style>

  .pre-light {
    color: #565656;
    padding: 0.5rem;
    background-color: #fff3cd;
  }

  /* Style the tooltip */
  a[tooltip]:hover:after {
    content: attr(tooltip);
    position: absolute;
    top: 100%;
    left: 50%;
    transform: translateX(-50%);
    background-color: #333;
    color: #fff;
    padding: 5px 10px;
    border-radius: 5px;
    white-space: nowrap;
  }

</style>
@endpush

@section('content')
@include('theme::iframes._agent')
<div class="px-3 pt-3" style="text-align: right;">
  <a href="{{ route('iframes.sca-editor') }}">
    {{ __('+ new') }}
  </a>
</div>
<div class="card mt-3">
  <div class="card-body">
    <div class="row">
      <div class="col">
        <div id="policies"></div>
      </div>
    </div>
    <div class="row mt-3">
      <div class="col">
        <div id="frameworks"></div>
      </div>
    </div>
    <div class="row mt-3">
      <div class="col">
        <div id="search"></div>
      </div>
      <div class="col col-auto">
        <div id="submit"></div>
      </div>
    </div>
  </div>
</div>
@if(\App\Helpers\OssecCheckScript::hasScript($checks))
<div class="row mt-3">
  <div class="col text-end">
    <b>{{ __('Script') }}</b>
    @if(\App\Helpers\OssecCheckScript::hasScript($checks, \App\Helpers\OssecCheckScript::OS_WINDOWS))
    <a
      href="data:text/plain;charset=utf-8,{{ rawurlencode(\App\Helpers\OssecCheckScript::generateScript($checks, \App\Helpers\OssecCheckScript::OS_WINDOWS)) }}"
      download="{{\App\Helpers\OssecCheckScript::scriptName($checks, \App\Helpers\OssecCheckScript::OS_WINDOWS)}}"
      tooltip="Windows">
      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
           stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
        <path
          d="M17.8 20l-12 -1.5c-1 -.1 -1.8 -.9 -1.8 -1.9v-9.2c0 -1 .8 -1.8 1.8 -1.9l12 -1.5c1.2 -.1 2.2 .8 2.2 1.9v12.1c0 1.2 -1.1 2.1 -2.2 1.9z"/>
        <path d="M12 5l0 14"/>
        <path d="M4 12l16 0"/>
      </svg>
    </a>
    @endif
    @if(\App\Helpers\OssecCheckScript::hasScript($checks, \App\Helpers\OssecCheckScript::OS_DEBIAN))
    <a
      href="data:text/plain;charset=utf-8,{{ rawurlencode(\App\Helpers\OssecCheckScript::generateScript($checks, \App\Helpers\OssecCheckScript::OS_DEBIAN)) }}"
      download="{{\App\Helpers\OssecCheckScript::scriptName($checks, \App\Helpers\OssecCheckScript::OS_DEBIAN)}}"
      tooltip="Debian">
      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
           stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"
           class="icon icon-tabler icons-tabler-outline icon-tabler-brand-debian">
        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
        <path
          d="M12 17c-2.397 -.943 -4 -3.153 -4 -5.635c0 -2.19 1.039 -3.14 1.604 -3.595c2.646 -2.133 6.396 -.27 6.396 3.23c0 2.5 -2.905 2.121 -3.5 1.5c-.595 -.621 -1 -1.5 -.5 -2.5"/>
        <path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"/>
      </svg>
    </a>
    @endif
    @if(\App\Helpers\OssecCheckScript::hasScript($checks, \App\Helpers\OssecCheckScript::OS_UBUNTU))
    <a
      href="data:text/plain;charset=utf-8,{{ rawurlencode(\App\Helpers\OssecCheckScript::generateScript($checks, \App\Helpers\OssecCheckScript::OS_UBUNTU)) }}"
      download="{{\App\Helpers\OssecCheckScript::scriptName($checks, \App\Helpers\OssecCheckScript::OS_UBUNTU)}}"
      tooltip="Debian">
      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
           stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"
           class="icon icon-tabler icons-tabler-outline icon-tabler-brand-ubuntu">
        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
        <path d="M12 5m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/>
        <path
          d="M17.723 7.41a7.992 7.992 0 0 0 -3.74 -2.162m-3.971 0a7.993 7.993 0 0 0 -3.789 2.216m-1.881 3.215a8 8 0 0 0 -.342 2.32c0 .738 .1 1.453 .287 2.132m1.96 3.428a7.993 7.993 0 0 0 3.759 2.19m4 0a7.993 7.993 0 0 0 3.747 -2.186m1.962 -3.43a8.008 8.008 0 0 0 .287 -2.131c0 -.764 -.107 -1.503 -.307 -2.203"/>
        <path d="M5 17m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/>
        <path d="M19 17m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/>
      </svg>
    </a>
    @endif
    @if(\App\Helpers\OssecCheckScript::hasScript($checks, \App\Helpers\OssecCheckScript::OS_CENTOS))
    <a
      href="data:text/plain;charset=utf-8,{{ rawurlencode(\App\Helpers\OssecCheckScript::generateScript($checks, \App\Helpers\OssecCheckScript::OS_CENTOS)) }}"
      download="{{\App\Helpers\OssecCheckScript::scriptName($checks, \App\Helpers\OssecCheckScript::OS_CENTOS)}}"
      tooltip="Debian">
      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
           stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"
           class="icon icon-tabler icons-tabler-outline icon-tabler-script">
        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
        <path
          d="M17 20h-11a3 3 0 0 1 0 -6h11a3 3 0 0 0 0 6h1a3 3 0 0 0 3 -3v-11a2 2 0 0 0 -2 -2h-10a2 2 0 0 0 -2 2v8"/>
      </svg>
    </a>
    @endif
  </div>
</div>
@endif
@foreach($checks as $check)
<div class="card mt-3 mb-3">
  <div class="card-header pb-0">
    <div class="row mt-2">
      <div class="col">
        <h6>
          @if(isset($check->created_by) || \Auth::user()?->isCywiseAdmin())
          <a href="{{ route('iframes.sca-editor', ['check_id' => $check->id]) }}">
            {{ $check->title }}
          </a>
          @else
          {{ $check->title }}
          @endif
        </h6>
      </div>
      <div class="col col-auto">
        @foreach($check->frameworks() as $f)
        <span class="lozenge information">{{ $f }}</span>&nbsp;
        @endforeach
      </div>
    </div>
  </div>
  <div class="card-body pt-0">
    <div class="row mt-2">
      <div class="col col-2 text-end">
        <b>{{ __('Description') }}</b>
      </div>
      <div class="col">
        {{ $check->description }}
      </div>
    </div>
    <div class="row mt-2">
      <div class="col col-2 text-end">
        <b>{{ __('Rationale') }}</b>
      </div>
      <div class="col">
        {{ $check->rationale }}
      </div>
    </div>
    <div class="row mt-2">
      <div class="col col-2 text-end">
        <b>{{ __('Remediation') }}</b>
      </div>
      <div class="col">
        {{ $check->remediation }}
      </div>
    </div>
    @if($check->references)
    <div class="row mt-2">
      <div class="col col-2 text-end">
        <b>{{ __('References') }}</b>
      </div>
      <div class="col">
        <ul class="mb-0" style="padding-left: 1rem;">
          @foreach($check->references as $reference)
          @if(\Illuminate\Support\Str::startsWith($reference, ['http://', 'https://']))
          <li><a href="{{ $reference }}" target="_blank">{{ $reference }}</a></li>
          @else
          <li>{{ $reference }}</li>
          @endif
          @endforeach
        </ul>
      </div>
    </div>
    @endif
    @if($check->hasMitreTactics())
    <div class="row mt-2">
      <div class="col col-2 text-end">
        <b>{{ __('Mitre Tactics') }}</b>
      </div>
      <div class="col">
        <ul class="mb-0" style="padding-left: 1rem;">
          @foreach($check->mitreTactics() as $tactic)
          <li><a href="https://attack.mitre.org/tactics/{{ $tactic }}/" target="_blank">{{ $tactic }}</a></li>
          @endforeach
        </ul>
      </div>
    </div>
    @endif
    @if($check->hasMitreTechniques())
    <div class="row mt-2">
      <div class="col col-2 text-end">
        <b>{{ __('Mitre Techniques') }}</b>
      </div>
      <div class="col">
        <ul class="mb-0" style="padding-left: 1rem;">
          @foreach($check->mitreTechniques() as $technique)
          <li><a href="https://attack.mitre.org/techniques/{{ $technique }}/" target="_blank">{{ $technique }}</a></li>
          @endforeach
        </ul>
      </div>
    </div>
    @endif
    @if($check->hasMitreMitigations())
    <div class="row mt-2">
      <div class="col col-2 text-end">
        <b>{{ __('Mitre Mitigations') }}</b>
      </div>
      <div class="col">
        <ul class="mb-0" style="padding-left: 1rem;">
          @foreach($check->mitreMitigations() as $mitigation)
          <li><a href="https://attack.mitre.org/mitigations/{{ $mitigation }}/" target="_blank">{{ $mitigation }}</a>
          </li>
          @endforeach
        </ul>
      </div>
    </div>
    @endif
    <div class="row mt-2">
      <div class="col col-2 text-end">
        <b>{{ __('Rule') }}</b>
      </div>
      <div class="col">
        <div style="display:grid;">
          <div class="overflow-auto">
            <pre class="mb-0 w-100 pre-light">{{ $check->rule }}</pre>
          </div>
        </div>
      </div>
    </div>
    @if(\App\Helpers\OssecCheckScript::hasScript($check))
    <div class="row mt-2">
      <div class="col col-2 text-end">
        <b>{{ __('Script') }}</b>
      </div>
      <div class="col">
        @if(\App\Helpers\OssecCheckScript::hasScript($check, \App\Helpers\OssecCheckScript::OS_WINDOWS))
        <a
          href="data:text/plain;charset=utf-8,{{ rawurlencode(\App\Helpers\OssecCheckScript::generateScript($check, \App\Helpers\OssecCheckScript::OS_WINDOWS)) }}"
          download="{{\App\Helpers\OssecCheckScript::scriptName($check, \App\Helpers\OssecCheckScript::OS_WINDOWS)}}"
          tooltip="Windows">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
               stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
            <path
              d="M17.8 20l-12 -1.5c-1 -.1 -1.8 -.9 -1.8 -1.9v-9.2c0 -1 .8 -1.8 1.8 -1.9l12 -1.5c1.2 -.1 2.2 .8 2.2 1.9v12.1c0 1.2 -1.1 2.1 -2.2 1.9z"/>
            <path d="M12 5l0 14"/>
            <path d="M4 12l16 0"/>
          </svg>
        </a>
        @endif
        @if(\App\Helpers\OssecCheckScript::hasScript($check, \App\Helpers\OssecCheckScript::OS_DEBIAN))
        <a
          href="data:text/plain;charset=utf-8,{{ rawurlencode(\App\Helpers\OssecCheckScript::generateScript($check, \App\Helpers\OssecCheckScript::OS_DEBIAN)) }}"
          download="{{\App\Helpers\OssecCheckScript::scriptName($check, \App\Helpers\OssecCheckScript::OS_DEBIAN)}}"
          tooltip="Debian">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
               stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"
               class="icon icon-tabler icons-tabler-outline icon-tabler-brand-debian">
            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
            <path
              d="M12 17c-2.397 -.943 -4 -3.153 -4 -5.635c0 -2.19 1.039 -3.14 1.604 -3.595c2.646 -2.133 6.396 -.27 6.396 3.23c0 2.5 -2.905 2.121 -3.5 1.5c-.595 -.621 -1 -1.5 -.5 -2.5"/>
            <path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"/>
          </svg>
        </a>
        @endif
        @if(\App\Helpers\OssecCheckScript::hasScript($check, \App\Helpers\OssecCheckScript::OS_UBUNTU))
        <a
          href="data:text/plain;charset=utf-8,{{ rawurlencode(\App\Helpers\OssecCheckScript::generateScript($check, \App\Helpers\OssecCheckScript::OS_UBUNTU)) }}"
          download="{{\App\Helpers\OssecCheckScript::scriptName($check, \App\Helpers\OssecCheckScript::OS_UBUNTU)}}"
          tooltip="Ubuntu">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
               stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"
               class="icon icon-tabler icons-tabler-outline icon-tabler-brand-ubuntu">
            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
            <path d="M12 5m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/>
            <path
              d="M17.723 7.41a7.992 7.992 0 0 0 -3.74 -2.162m-3.971 0a7.993 7.993 0 0 0 -3.789 2.216m-1.881 3.215a8 8 0 0 0 -.342 2.32c0 .738 .1 1.453 .287 2.132m1.96 3.428a7.993 7.993 0 0 0 3.759 2.19m4 0a7.993 7.993 0 0 0 3.747 -2.186m1.962 -3.43a8.008 8.008 0 0 0 .287 -2.131c0 -.764 -.107 -1.503 -.307 -2.203"/>
            <path d="M5 17m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/>
            <path d="M19 17m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/>
          </svg>
        </a>
        @endif
        @if(\App\Helpers\OssecCheckScript::hasScript($check, \App\Helpers\OssecCheckScript::OS_CENTOS))
        <a
          href="data:text/plain;charset=utf-8,{{ rawurlencode(\App\Helpers\OssecCheckScript::generateScript($check, \App\Helpers\OssecCheckScript::OS_CENTOS)) }}"
          download="{{\App\Helpers\OssecCheckScript::scriptName($check, \App\Helpers\OssecCheckScript::OS_CENTOS)}}"
          tooltip="CentOS">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
               stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"
               class="icon icon-tabler icons-tabler-outline icon-tabler-script">
            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
            <path
              d="M17 20h-11a3 3 0 0 1 0 -6h11a3 3 0 0 0 0 6h1a3 3 0 0 0 3 -3v-11a2 2 0 0 0 -2 -2h-10a2 2 0 0 0 -2 2v8"/>
          </svg>
        </a>
        @endif
      </div>
    </div>
    @endif
  </div>
</div>
@endforeach
@endsection

@push('scripts')
<script>

  let selectedPolicy = null;
  let selectedFramework = null;
  let searchText = null;

  const queryString = () => '?' + (selectedPolicy ? '&policy=' + selectedPolicy.uid : '') + (selectedFramework
    ? '&framework=' + selectedFramework : '') + (searchText ? '&search=' + searchText : '');

  const elPolicies = new com.computablefacts.blueprintjs.MinimalSelect(document.getElementById('policies'),
    item => item.name);
  elPolicies.defaultText = "{{ __('Select policy...') }}";
  elPolicies.items = @json($policies);
  selectedPolicy = elPolicies.items.find(policy => policy.uid === "{{ $policy }}")
  elPolicies.selectedItem = selectedPolicy;
  elPolicies.onSelectionChange(item => {
    selectedPolicy = item;
    selectedFramework = null;
    searchText = null;
    elFrameworks.disabled = !selectedPolicy;
    elSearch.disabled = !selectedPolicy;
    window.location = window.location.href.split('?')[0] + queryString();
  });

  const elFrameworks = new com.computablefacts.blueprintjs.MinimalSelect(document.getElementById('frameworks'));
  elFrameworks.defaultText = "{{ __('Select framework...') }}";
  elFrameworks.items = @json($frameworks);
  selectedFramework = elFrameworks.items.find(framework => framework === "{{ $framework }}");
  elFrameworks.selectedItem = selectedFramework;
  elFrameworks.onSelectionChange(item => {
    selectedFramework = item;
    searchText = null;
    window.location = window.location.href.split('?')[0] + queryString();
  });
  elFrameworks.disabled = !selectedPolicy;

  const elSearch = new com.computablefacts.blueprintjs.MinimalTextInput(document.getElementById('search'),
    "{{ $search }}");
  elSearch.icon = 'filter';
  elSearch.placeholder = "{{ __('Enter one or more keywords...') }}";
  elSearch.disabled = !selectedPolicy;

  const elSubmit = new com.computablefacts.blueprintjs.MinimalButton(document.getElementById('submit'),
    "{{ __('Search') }}");
  elSubmit.rightIcon = 'chevron-right';
  elSubmit.disabled = !selectedPolicy;
  elSubmit.onClick(() => {
    searchText = elSearch.value;
    window.location = window.location.href.split('?')[0] + queryString();
  });

</script>
@endpush
