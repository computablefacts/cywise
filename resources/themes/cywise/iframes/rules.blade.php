@extends('theme::iframes.app')

@section('content')
@include('theme::iframes._agent')
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
            @if(isset($rule->created_by) || \Auth::user()?->isCywiseAdmin())
              <a href="{{ route('iframes.rules-editor', ['rule_id' => $rule->id]) }}">
                {{ $rule->displayName() }}
              </a>
            @else
              {{ $rule->displayName() }}
            @endif
            &nbsp;{!! $rule->category ? '<span style="color:#ff9704">/</span>&nbsp;' . $rule->category : '' !!}
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

