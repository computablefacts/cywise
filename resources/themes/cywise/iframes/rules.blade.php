@extends('theme::iframes.app')

@push('styles')
<style>

  .pre-light {
    color: #565656;
    padding: 0.5rem;
    background-color: #fff3cd;
  }

</style>
@endpush

@section('content')
@include('theme::iframes._agent')
<div class="px-3 pt-3" style="text-align: right;">
  <a href="{{ route('iframes.rules-editor') }}">
    {{ __('+ new') }}
  </a>
</div>
@if($rules->isEmpty())
<div class="card mt-3 mb-3">
  <div class="card-body">
    <div class="row">
      <div class="col">
        {{ __('None.') }}
      </div>
    </div>
  </div>
</div>
@else
@foreach($rules as $rule)
<div class="card mt-3 mb-3">
  <div class="card-header pb-0">
    <div class="row mt-2">
      <div class="col">
        <h6>
          @if(isset($rule->created_by) || \Auth::user()?->isCywiseAdmin())
          <a href="{{ route('iframes.rules-editor', ['rule_id' => $rule->id]) }}">
            {{ $rule->displayName() }}
          </a>
          @else
          {{ $rule->displayName() }}
          @endif
        </h6>
      </div>
      <div class="col col-auto">
        @if(!empty($rule->mitreAttckTactics()))
        @foreach($rule->mitreAttckTactics() as $tactic)
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
        <div class="text-muted">
          @if(\Illuminate\Support\Str::startsWith($rule->comments, 'Needs further work on the collected data to be
          useful'))
          {{ $rule->description }}
          @else
          {{ $rule->comments }}
          @endif
        </div>
      </div>
    </div>
    <div class="row mt-2">
      <div class="col col-2 text-end">
        <b>{{ __('Platform') }}</b>
      </div>
      <div class="col">
        <span class="lozenge information">
          {{ $rule->platform->value }}
        </span>&nbsp;
        <span class="lozenge information">
          {{ \Carbon\CarbonInterval::seconds($rule->interval)->cascade()->forHumans() }}
        </span>
      </div>
    </div>
    <div class="row mt-2">
      <div class="col col-2 text-end">
        <b>{{ __('IoC') }}</b>
      </div>
      <div class="col">
        @if($rule->is_ioc)
        <span class="lozenge error">{{ __('yes') }}</span>&nbsp;
        @else
        <span class="lozenge success">{{ __('no') }}</span>&nbsp;
        @endif
        @if($rule->score >= 75)
        <span class="lozenge error">{{ $rule->score }}&nbsp;/&nbsp;100</span>
        @elseif($rule->score >= 50)
        <span class="lozenge warning">{{ $rule->score }}&nbsp;/&nbsp;100</span>
        @elseif($rule->score >= 25)
        <span class="lozenge information">{{ $rule->score }}&nbsp;/&nbsp;100</span>
        @else
        <span class="lozenge neutral">{{ $rule->score }}&nbsp;/&nbsp;100</span>
        @endif
      </div>
    </div>
    @if(!empty($rule->attck))
    <div class="row mt-2">
      <div class="col col-2 text-end">
        <b>{{ __('Mitre') }}</b>
      </div>
      <div class="col">
        @foreach(explode(',', $rule->attck) as $attck)
        @if(\Illuminate\Support\Str::startsWith($attck, 'TA'))
        <a href="https://attack.mitre.org/tactics/{{ $attck }}/" target="_blank">{{ $attck }}</a>&nbsp;
        @else
        <a href="https://attack.mitre.org/techniques/{{ $attck }}/" target="_blank">{{ $attck }}</a>&nbsp;
        @endif
        @endforeach
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
            <pre class="mb-0 w-100 pre-light">{{ $rule->query }}</pre>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endforeach
@endif
@endsection

