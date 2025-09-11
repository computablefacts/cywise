@extends('theme::iframes.app')

@section('content')
<div class="row pt-3">
  <!-- CYBERTODO : BEGIN -->
  @if(count($todo) > 0)
  <div class="col-4 pe-0">
    <div class="card">
      <div class="card-body">
        <h6 class="card-title">
          {!! __('Your 5 most critical vulnerabilities to fix!') !!}
        </h6>
        <div class="card-text mb-3">
          @foreach($todo as $item)
          <div class="d-flex justify-content-start align-items-center mb-2">
            @if($item->level === 'High')
            <span class="dot-red"></span>
            @elseif ($item->level === 'Medium')
            <span class="dot-orange"></span>
            @elseif($item->level === 'Low')
            <span class="dot-green"></span>
            @else
            <span class="dot-blue"></span>
            @endif
            &nbsp;<a href="{{ route('iframes.vulnerabilities') }}#vid-{{ $item->id }}" class="link text-truncate">
              {{ $item->asset()->asset }}
            </a>
          </div>
          <div class="d-flex justify-content-start align-items-center text-truncate mb-3">
            @if(empty($item->cve_id))
            {{ $item->title }}
            @else
            {{ $item->cve_id }}&nbsp;/&nbsp;{{ $item->title }}
            @endif
          </div>
          @endforeach
        </div>
      </div>
    </div>
  </div>
  @endif
  <!-- CYBERTODO : END -->
  <div class="col">
    <!-- ASSETS : BEGIN -->
    <div class="row">
      <div class="col">
        <div class="card">
          <div class="card-body p-3">
            <div class="row align-items-center">
              <div class="col-auto">
                <div class="d-flex align-content-center">
                  <span class="bg-blue text-white avatar">
                    <span class="bp4-icon bp4-icon-globe-network"></span>
                  </span>
                </div>
              </div>
              <div class="col">
                <div class="h5 mb-0">
                  <b>{{ $nb_monitored + $nb_monitorable }}</b>
                </div>
                <div class="text-muted">
                  <a href="{{ route('iframes.assets') }}" class="link">
                    {{ __('Assets') }}
                  </a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col ps-0">
        <div class="card">
          <div class="card-body p-3">
            <div class="row align-items-center">
              <div class="col-auto">
                <div class="d-flex align-content-center">
                  <span class="bg-blue text-white avatar">
                    <span class="bp4-icon bp4-icon-globe-network"></span>
                  </span>
                </div>
              </div>
              <div class="col">
                <div class="h5 mb-0">
                  <b>{{ $nb_monitored }}</b>
                </div>
                <div class="text-muted">
                  <a href="{{ route('iframes.assets', [ 'status' => 'monitored' ]) }}" class="link">
                    {{ __('Assets Monitored') }}
                  </a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col ps-0">
        <div class="card">
          <div class="card-body p-3">
            <div class="row align-items-center">
              <div class="col-auto">
                <div class="d-flex align-content-center">
                  <span class="bg-blue text-white avatar">
                    <span class="bp4-icon bp4-icon-globe-network"></span>
                  </span>
                </div>
              </div>
              <div class="col">
                <div class="h5 mb-0">
                  <b>{{ $nb_monitorable }}</b>
                </div>
                <div class="text-muted">
                  <a href="{{ route('iframes.assets', [ 'status' => 'monitorable' ]) }}" class="link">
                    {{ __('Assets Monitorable') }}
                  </a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!-- ASSETS : END -->
    <!-- VULNERABILITIES : BEGIN -->
    <div class="row pt-3">
      <div class="col">
        <div class="card">
          <div class="card-body p-3">
            <div class="row align-items-center">
              <div class="col-auto">
                <div class="d-flex align-content-center">
                  <span class="bg-red text-white avatar">
                    <span class="bp4-icon bp4-icon-issue"></span>
                  </span>
                </div>
              </div>
              <div class="col">
                <div class="h5 mb-0">
                  <b>{{ $nb_high }}</b>
                </div>
                <div class="text-muted">
                  <a href="{{ route('iframes.vulnerabilities', [ 'level' => 'high' ]) }}" class="link">
                    {{ __('High') }}
                  </a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col ps-0">
        <div class="card">
          <div class="card-body p-3">
            <div class="row align-items-center">
              <div class="col-auto">
                <div class="d-flex align-content-center">
                  <span class="bg-orange text-white avatar">
                    <span class="bp4-icon bp4-icon-issue"></span>
                  </span>
                </div>
              </div>
              <div class="col">
                <div class="h5 mb-0">
                  <b>{{ $nb_medium }}</b>
                </div>
                <div class="text-muted">
                  <a href="{{ route('iframes.vulnerabilities', [ 'level' => 'medium' ]) }}" class="link">
                    {{ __('Medium') }}
                  </a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col ps-0">
        <div class="card">
          <div class="card-body p-3">
            <div class="row align-items-center">
              <div class="col-auto">
                <div class="d-flex align-content-center">
                  <span class="bg-green text-white avatar">
                    <span class="bp4-icon bp4-icon-issue"></span>
                  </span>
                </div>
              </div>
              <div class="col">
                <div class="h5 mb-0">
                  <b>{{ $nb_low }}</b>
                </div>
                <div class="text-muted">
                  <a href="{{ route('iframes.vulnerabilities', [ 'level' => 'low' ]) }}" class="link">
                    {{ __('Low') }}
                  </a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!-- VULNERABILITIES : END -->
    <!-- ACTIONS : BEGIN -->
    <div class="row pt-3">
      <div class="col pe-0">
        <!-- ACTION PROTECT : BEGIN -->
        <div class="card">
          <div class="card-body">
            <h6 class="card-title">{{ __('Would you like to protect a new domain?') }}</h6>
            <div class="card-text mb-3">
              {{ __('Enter a domain name or an IP address belonging to you below:') }}
            </div>
            <form>
              <div class="row">
                <div class="col">
                  <input type="text"
                         class="form-control"
                         id="asset"
                         placeholder="example.com ou 93.184.215.14"
                         autofocus>
                </div>
              </div>
              <div class="row mt-3">
                <div class="col align-content-center">
                  <button type="button"
                          onclick="createAsset()"
                          class="btn btn-primary"
                          style="width: 100%;">
                    {{ __('Monitor >') }}
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>
        <!-- ACTION PROTECT : END -->
      </div>
      <div class="col">
        <!-- ACTION CYBERBUDDY : BEGIN -->
        <div class="card">
          <div class="card-body">
            <h6 class="card-title">
              {{ __('Do you have a question related to Cyber?') }}
            </h6>
            <div class="card-text mb-3">
              {{ __('Click here to launch CyberBuddy:') }}
            </div>
            <form>
              <div class="row">
                <div class="col align-content-center">
                  <a href="{{ route('iframes.cyberbuddy') }}" class="btn btn-primary" style="width: 100%;">
                    {{ __('Start Conversation >') }}
                  </a>
                </div>
              </div>
            </form>
          </div>
        </div>
        <!-- ACTION CYBERBUDDY : END -->
      </div>
    </div>
    <!-- ACTIONS : BEGIN -->
  </div>
</div>
<!-- APPS : BEGIN -->
@php
$apps = \App\Models\YnhServer::forUser(request()->user())
->flatMap(fn(\App\Models\YnhServer $server) => $server->applications)
->sortBy([
['server.name', 'asc'],
['name', 'asc'],
], SORT_NATURAL | SORT_FLAG_CASE);
@endphp
@if($apps->isNotEmpty())
<div class="row pt-3">
  <div class="col">
    <div class="card">
      <div class="card-body p-0">
        <table class="table">
          <thead>
          <tr>
            <th>{{ __('Server') }}</th>
            <th>{{ __('Name') }}</th>
            <th>{{ __('Description') }}</th>
            <th>{{ __('Sku') }}</th>
            <th>{{ __('Version') }}</th>
          </tr>
          </thead>
          <tbody>
          @foreach($apps as $app)
          <tr>
            <td>
          <span class="font-lg mb-3 fw-bold">
            {{ $app->server->name }}
          </span>
            </td>
            <td>
              <span class="font-lg mb-3 fw-bold">
                <a href="https://{{ $app->path }}" target="_blank">
                  {{ $app->name }}
                </a>
              </span>
            </td>
            <td>
              {{ $app->description }}
            </td>
            <td>
              {{ $app->sku }}
            </td>
            <td>
              {{ $app->version }}
            </td>
          </tr>
          @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endif
<!-- APPS : END -->
<!-- AGENT : BEGIN -->
<!--
<div class="row pt-3">
  <div class="col">
    <div class="card">
      <div class="card-body">
        <h6 class="card-title text-truncate">
          {{ __('Would you like to protect a new server?') }}
        </h6>
        <ul class="nav nav-tabs" role="tablist">
          <li class="nav-item" role="presentation">
            <a class="nav-link active" data-bs-toggle="tab" href="#tab-linux" role="tab"
               aria-controls="tab-linux" aria-selected="true">
              {{ __('Linux') }}
            </a>
          </li>
          <li class="nav-item" role="presentation">
            <a class="nav-link" data-bs-toggle="tab" href="#tab-windows" role="tab"
               aria-controls="tab-windows" aria-selected="false">
              {{ __('Windows') }}
            </a>
          </li>
          <li class="nav-item" role="presentation">
            <a class="nav-link disabled" data-bs-toggle="tab" href="#tab-macos" role="tab"
               aria-controls="tab-macos" aria-selected="false">
              {{ __('MacOS') }}
            </a>
          </li>
        </ul>
        <div class="tab-content pt-5" id="tab-content">
          <div class="tab-pane active" id="tab-linux" role="tabpanel" aria-labelledby="tab-linux">
            {{ __('To monitor a new Linux server, log in as root and execute this command line:') }}
            <br><br>
            <pre class="mb-0">
curl -s "{{ app_url() }}/setup/script?api_token={{ Auth::user()->sentinelApiToken() }}&server_ip=$(curl -s ipinfo.io | jq -r '.ip')&server_name=$(hostname)" | bash
            </pre>
          </div>
          <div class="tab-pane" id="tab-windows" role="tabpanel" aria-labelledby="tab-windows">
            {{ __('To monitor a new Windows server, log in as administrator and execute this command line:') }}
            <br><br>
            <pre class="mb-0">
Invoke-WebRequest -Uri "{{ app_url() }}/setup/script?api_token={{ Auth::user()->sentinelApiToken() }}&server_ip=$((Invoke-RestMethod -Uri 'https://ipinfo.io').ip)&server_name=$($env:COMPUTERNAME)&platform=windows" -UseBasicParsing | Invoke-Expression
            </pre>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
-->
<!-- AGENT : END -->
<!-- HONEYPOTS : BEGIN -->
@if(count($honeypots) > 0)
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/charts.css/dist/charts.min.css">
<div class="row pt-3 pb-3 pe-3">
  @foreach($honeypots as $honeypot)
  <div class="col pe-0">
    <div class="card">
      <div class="card-body">
        <h6 class="card-title text-truncate">
          {{ $honeypot['type'] }}&nbsp;<span style="color: var(--c-orange-light);">/</span>&nbsp;{{ $honeypot['name'] }}
        </h6>
        @if(\Illuminate\Support\Str::endsWith($honeypot['name'], '.cywise.io'))
        <p>{{ __('Would you like to redirect one of your domains to this honeypot? Contact support!') }}</p>
        @endif
        @if(count($honeypot['counts']) <= 0)
        <p>{{ __('No recent events.') }}</p>
        @else
        <div class="card-text mb-3">
          <table
            class="charts-css column hide-data show-primary-axis show-3-secondary-axes data-spacing-1 multiple stacked">
            <thead>
            <tr>
              <th scope="col">{{ __('Date') }}</th>
              <th scope="col">{{ __('Human or Targeted') }}</th>
              <th scope="col">{{ __('Bots') }}</th>
            </tr>
            </thead>
            <tbody>
            @foreach($honeypot['counts'] as $count)
            <tr>
              <th scope="row">{{ \Illuminate\Support\Str::after($count['date'], '-') }}</th>
              <td style="--size: calc({{ $count['human_or_targeted'] }} / {{ $honeypot['max'] }});">
                <span class="data">{{ $count['human_or_targeted'] }}</span>
                <span class="tooltip">
                  {{ __('Human or Targeted') }}: {{ $count['human_or_targeted'] }}<br>{{ \Illuminate\Support\Str::after($count['date'], '-') }}
                </span>
              </td>
              <td style="--size: calc({{ $count['not_human_or_targeted'] }} / {{ $honeypot['max'] }});">
                <span class="data">{{ $count['not_human_or_targeted'] }}</span>
                <span class="tooltip">
                  {{ __('Bots') }}: {{ $count['not_human_or_targeted'] }}<br>{{ \Illuminate\Support\Str::after($count['date'], '-') }}
                </span>
              </td>
            </tr>
            @endforeach
            </tbody>
          </table>
        </div>
        @endif
        @if(isset($most_recent_honeypot_events[$honeypot['name']]))
        <div class="card-text mb-3">
          <table class="table">
            <thead>
            <tr>
              <th colspan="3">
                {!! __('The&nbsp;<span style="color: var(--c-orange-light);">5</span>&nbsp;most recent attacks') !!}
              </th>
            </tr>
            </thead>
            <tbody>
            @foreach($most_recent_honeypot_events[$honeypot['name']]['events'] as $event)
            <tr title="{{ $event['event_details'] }}">
              <td style="color: var(--c-blue);">
                @if($event['attacker_name'] !== '-')
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                     class="icon icon-tabler icons-tabler-outline icon-tabler-user">
                  <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                  <path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0"/>
                  <path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/>
                </svg>
                @else
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                     class="icon icon-tabler icons-tabler-outline icon-tabler-robot">
                  <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                  <path d="M6 4m0 2a2 2 0 0 1 2 -2h8a2 2 0 0 1 2 2v4a2 2 0 0 1 -2 2h-8a2 2 0 0 1 -2 -2z"/>
                  <path d="M12 2v2"/>
                  <path d="M9 12v9"/>
                  <path d="M15 12v9"/>
                  <path d="M5 16l4 -2"/>
                  <path d="M15 14l4 2"/>
                  <path d="M9 18h6"/>
                  <path d="M10 8v.01"/>
                  <path d="M14 8v.01"/>
                </svg>
                @endif
              </td>
              <td>
                {{ $event['timestamp'] }}
              </td>
              <td>
                {{ $event['event_type'] }}
              </td>
            </tr>
            @endforeach
            </tbody>
          </table>
        </div>
        @endif
      </div>
    </div>
  </div>
  @endforeach
</div>
@endif
<!-- HONEYPOTS : END -->
@endsection

@push('scripts')
<script>

  function createAsset() {
    const asset = document.querySelector('#asset').value;
    createAssetApiCall(asset, true, () => toaster.toastSuccess("{{ __('The monitoring started.') }}"));
  }

</script>
@endpush
