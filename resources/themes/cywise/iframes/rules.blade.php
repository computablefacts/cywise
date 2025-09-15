@extends('theme::iframes.app')

@section('content')
<div class="card mt-3">
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
<div class="px-3 pt-3" style="text-align: right;">
  <a href="{{ route('iframes.rules-editor') }}">
    {{ __('+ new') }}
  </a>
</div>
<div class="card mt-3 mb-3">
  @if($rules->isEmpty())
  <div class="card-body">
    <div class="row">
      <div class="col">
        {{ __('None.') }}
      </div>
    </div>
  </div>
  @else
  <div class="card-body p-0">
    <table class="table table-hover mb-0">
      <thead>
      <tr>
        <th>{{ __('Name') }}</th>
        <th>{{ __('IOC') }}</th>
        <th class="text-end">{{ __('Weight') }}</th>
        <!-- <th>{{ __('Version') }}</th> -->
        <th>{{ __('Interval') }}</th>
        <th>{{ __('Platform') }}</th>
      </tr>
      </thead>
      <tbody>
      @foreach($rules as $rule)
      <tr>
        <td>
          <span class="font-lg mb-3 fw-bold">
            @if(isset($rule->created_by))<a href="{{ route('iframes.rules-editor', ['rule_id' => $rule->id]) }}">{{ $rule->displayName() }}</a>@else{{ $rule->displayName() }}@endif&nbsp;{!! $rule->category ? '<span style="color:#ff9704">/</span>&nbsp;' . $rule->category : '' !!}
          </span>
          <div class="text-muted">
            @if(\Illuminate\Support\Str::startsWith($rule->comments, 'Needs further work on the collected data to be useful'))
            {{ $rule->description }}
            @else
            {{ $rule->comments }}
            @endif
          </div>
          @if($rule->attck)
          <div class="text-muted">
            @foreach($rule->mitreAttckTactics() as $tactic)
            <span class="lozenge new">{{ $tactic }}</span>&nbsp;
            @endforeach
          </div>
          <div class="text-muted">
            @foreach(explode(',', $rule->attck) as $attck)
            @if(\Illuminate\Support\Str::startsWith($attck, 'TA'))
            <a href="https://attack.mitre.org/tactics/{{ $attck }}/">
              {{ $attck }}
            </a>&nbsp;
            @else
            <a href="https://attack.mitre.org/techniques/{{ $attck }}/">
              {{ $attck }}
            </a>&nbsp;
            @endif
            @endforeach
          </div>
          @endif
        </td>
        <td>
          @if($rule->is_ioc)
          <span class="lozenge error">{{ __('yes') }}</span>
          @else
          <span class="lozenge success">{{ __('no') }}</span>
          @endif
        </td>
        <td class="text-end">
          <span class="lozenge information">{{ $rule->score }}</span>
        </td>
        <!-- <td>
          {{ $rule->version ? $rule->version : '-' }}
        </td> -->
        <td>
          {{ \Carbon\CarbonInterval::seconds($rule->interval)->cascade()->forHumans(); }}
        </td>
        <td>
          <span class="lozenge new">
            {{ __($rule->platform->value) }}
          </span>
        </td>
      </tr>
      @endforeach
      </tbody>
    </table>
  </div>
  @endif
</div>
@endsection

