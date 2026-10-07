<?php

use App\Http\Controllers\Iframes\RulesController;
use App\Http\Middleware\CheckPermissionsHttpRequest;
use App\Http\Middleware\LogHttpRequests;
use Illuminate\Http\Request;
use function Laravel\Folio\{middleware, name, render};

middleware([LogHttpRequests::class, 'auth', CheckPermissionsHttpRequest::class]);
name('rules');
render(function (Request $request) {
  return app(RulesController::class)($request);
});
?>

<x-layouts.app>
  @php
    // Same score → badge level mapping as the "Selected rule" card of the events page
    $scoreLevel = fn(int $score) => match (true) { $score >= 75 => 'high', $score >= 50 => 'medium', $score >= 25 => 'info', default => 'neutral' };
  @endphp

  <div class="ui:mx-auto ui:flex ui:w-full ui:max-w-7xl ui:flex-col ui:gap-6 ui:px-4 ui:py-8 ui:lg:px-8">

    <x-ui.page-header :title="__('Security Rules')"
                      :subtitle="__('Osquery rules run by the agent on your servers to detect suspicious activity.')">
      <x-slot:actions>
        <x-ui.button icon="plus" :href="route('rules-editor')">{{ __('New rule') }}</x-ui.button>
      </x-slot:actions>
    </x-ui.page-header>

    <x-ui.disclosure icon="terminal-window" :title="__('Would you like to protect a new server?')">
      @include('theme::iframes.dashboard._server-install')
    </x-ui.disclosure>

    @if($rules->isEmpty())
      <x-ui.card>
        <x-ui.empty icon="magnifying-glass">{{ __('None.') }}</x-ui.empty>
      </x-ui.card>
    @endif

    {{-- One card per rule, same layout as the events page "Selected rule" card --}}
    @foreach($rules as $rule)
      <x-ui.card>
        <div class="ui:flex ui:flex-col ui:gap-4">
          <div class="ui:flex ui:flex-wrap ui:items-start ui:justify-between ui:gap-3">
            <h2 class="ui:m-0 ui:text-base ui:font-semibold ui:text-ink">
              @if(isset($rule->created_by) || \Auth::user()?->isCywiseAdmin())
                <a href="{{ route('rules-editor', ['rule_id' => $rule->id]) }}" class="ui:text-ink! ui:hover:text-brand-600!">{{ $rule->displayName() }}</a>
              @else
                {{ $rule->displayName() }}
              @endif
            </h2>
            <div class="ui:flex ui:flex-wrap ui:gap-1.5">
              @foreach($rule->mitreAttckTactics() as $tactic)
                <x-ui.tag>{{ \Illuminate\Support\Str::lower($tactic) }}</x-ui.tag>
              @endforeach
            </div>
          </div>
          <p class="ui:m-0 ui:text-sm ui:text-slate-600">
            @if(\Illuminate\Support\Str::startsWith($rule->comments, 'Needs further work on the collected data to be useful'))
              {{ $rule->description }}
            @else
              {{ $rule->comments }}
            @endif
          </p>
          <dl class="ui:m-0 ui:grid ui:gap-3 ui:text-sm ui:sm:grid-cols-3">
            <div>
              <dt class="ui:text-xs ui:font-semibold ui:uppercase ui:tracking-wide ui:text-slate-400">{{ __('Platform') }}</dt>
              <dd class="ui:m-0 ui:mt-1 ui:flex ui:gap-1.5">
                <x-ui.badge level="info">{{ $rule->platform->value }}</x-ui.badge>
                <x-ui.badge>{{ \Carbon\CarbonInterval::seconds($rule->interval)->cascade()->forHumans() }}</x-ui.badge>
              </dd>
            </div>
            <div>
              <dt class="ui:text-xs ui:font-semibold ui:uppercase ui:tracking-wide ui:text-slate-400">{{ __('IoC') }}</dt>
              <dd class="ui:m-0 ui:mt-1 ui:flex ui:gap-1.5">
                <x-ui.badge :level="$rule->is_ioc ? 'high' : 'low'">{{ $rule->is_ioc ? __('yes') : __('no') }}</x-ui.badge>
                <x-ui.badge :level="$scoreLevel($rule->score)">{{ $rule->score }} / 100</x-ui.badge>
              </dd>
            </div>
            @if(!empty($rule->attck))
              <div>
                <dt class="ui:text-xs ui:font-semibold ui:uppercase ui:tracking-wide ui:text-slate-400">{{ __('Mitre') }}</dt>
                <dd class="ui:m-0 ui:mt-1 ui:flex ui:flex-wrap ui:gap-2">
                  {{-- TA0001 is a tactic, T1059 a technique --}}
                  @foreach(explode(',', $rule->attck) as $attck)
                    <a href="https://attack.mitre.org/{{ \Illuminate\Support\Str::startsWith($attck, 'TA') ? 'tactics' : 'techniques' }}/{{ $attck }}/" target="_blank">{{ $attck }}</a>
                  @endforeach
                </dd>
              </div>
            @endif
          </dl>
          <pre class="ui:m-0 ui:overflow-auto ui:rounded-lg ui:bg-slate-50 ui:p-3 ui:text-xs ui:text-slate-700">{{ $rule->query }}</pre>
        </div>
      </x-ui.card>
    @endforeach
  </div>
</x-layouts.app>
