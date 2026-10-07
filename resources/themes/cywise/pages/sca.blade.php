<?php

use App\Http\Controllers\Iframes\ScaController;
use App\Http\Middleware\CheckPermissionsHttpRequest;
use App\Http\Middleware\LogHttpRequests;
use Illuminate\Http\Request;
use function Laravel\Folio\{middleware, name, render};

middleware([LogHttpRequests::class, 'auth', CheckPermissionsHttpRequest::class]);
name('sca');
render(function (Request $request) {
  return app(ScaController::class)($request);
});
?>

<x-layouts.app>
  @php
    $script = \App\Helpers\OssecCheckScript::class;

    // Remediation scripts of a check (or of all the listed checks), one download per OS:
    //   [['label' => 'Windows', 'href' => 'data:text/plain;…', 'name' => 'xxx.ps1'], …]
    $scripts = fn($subject) => collect([$script::OS_WINDOWS => 'Windows', $script::OS_DEBIAN => 'Debian', $script::OS_UBUNTU => 'Ubuntu', $script::OS_CENTOS => 'CentOS'])
      ->filter(fn($label, $os) => $script::hasScript($subject, $os))
      ->map(fn($label, $os) => [
        'label' => $label,
        'href' => 'data:text/plain;charset=utf-8,' . rawurlencode($script::generateScript($subject, $os)),
        'name' => $script::scriptName($subject, $os),
      ]);

    $allScripts = $scripts($checks);
    $dt = 'ui:text-xs ui:font-semibold ui:uppercase ui:tracking-wide ui:text-slate-400';
  @endphp

  <div class="ui:mx-auto ui:flex ui:w-full ui:max-w-7xl ui:flex-col ui:gap-6 ui:px-4 ui:py-8 ui:lg:px-8">

    <x-ui.page-header :title="__('Security Checks Automation')"
                      :subtitle="__('Configuration checks run by the agent on your servers, by security policy.')">
      <x-slot:actions>
        <x-ui.button icon="plus" :href="route('sca-editor')">{{ __('New rule') }}</x-ui.button>
      </x-slot:actions>
    </x-ui.page-header>

    <x-ui.disclosure icon="terminal-window" :title="__('Would you like to protect a new server?')">
      @include('theme::iframes.dashboard._server-install')
    </x-ui.disclosure>

    {{-- Filters. Changing the policy resets the framework and the keywords; changing the framework resets the keywords. --}}
    <form method="get" action="{{ route('sca') }}"
          class="ui:m-0 ui:grid ui:items-end ui:gap-3 ui:rounded-xl ui:border ui:border-solid ui:border-line ui:bg-surface ui:p-4 ui:shadow-xs ui:sm:grid-cols-2 ui:lg:grid-cols-[1fr_1fr_2fr_auto]">
      <x-ui.field :label="__('Policy')" for="policy">
        <x-ui.select id="policy" name="policy"
                     onchange="this.form.framework.value = ''; this.form.search.value = ''; this.form.submit()">
          <option value="">{{ __('Select policy...') }}</option>
          @foreach($policies as $p)
            <option value="{{ $p->uid }}" @selected($policy === $p->uid)>{{ $p->name }}</option>
          @endforeach
        </x-ui.select>
      </x-ui.field>
      <x-ui.field :label="__('Framework')" for="framework">
        <x-ui.select id="framework" name="framework" :disabled="!$policy"
                     onchange="this.form.search.value = ''; this.form.submit()">
          <option value="">{{ __('Select framework...') }}</option>
          @foreach($frameworks as $f)
            <option value="{{ $f }}" @selected($framework === $f)>{{ $f }}</option>
          @endforeach
        </x-ui.select>
      </x-ui.field>
      <x-ui.field :label="__('Keywords')" for="search">
        <x-ui.input id="search" name="search" value="{{ $search }}" :disabled="!$policy"
                    :placeholder="__('Enter one or more keywords...')"/>
      </x-ui.field>
      <x-ui.button type="submit" icon="magnifying-glass" :disabled="!$policy">{{ __('Search') }}</x-ui.button>
    </form>

    {{-- Remediation scripts of all the listed checks --}}
    @if($allScripts->isNotEmpty())
      <div class="ui:flex ui:flex-wrap ui:items-center ui:justify-end ui:gap-2">
        <span class="ui:text-sm ui:font-medium ui:text-slate-600">{{ __('Script') }}</span>
        @foreach($allScripts as $s)
          <x-ui.button variant="secondary" size="sm" icon="download-simple" :href="$s['href']" download="{{ $s['name'] }}">
            {{ $s['label'] }}
          </x-ui.button>
        @endforeach
      </div>
    @endif

    @if($checks->isEmpty())
      <x-ui.card>
        <x-ui.empty icon="list-checks">{{ $policy ? __('None.') : __('Select a policy to see its checks.') }}</x-ui.empty>
      </x-ui.card>
    @endif

    {{-- One card per check --}}
    @foreach($checks as $check)
      @php
        $checkScripts = $scripts($check);
      @endphp
      <x-ui.card>
        <div class="ui:flex ui:flex-col ui:gap-4">
          <div class="ui:flex ui:flex-wrap ui:items-start ui:justify-between ui:gap-3">
            <h2 class="ui:m-0 ui:text-base ui:font-semibold ui:text-ink">
              @if(isset($check->created_by) || \Auth::user()?->isCywiseAdmin())
                <a href="{{ route('sca-editor', ['check_id' => $check->id]) }}" class="ui:text-ink! ui:hover:text-brand-600!">{{ $check->title }}</a>
              @else
                {{ $check->title }}
              @endif
            </h2>
            <div class="ui:flex ui:flex-wrap ui:gap-1.5">
              @foreach($check->frameworks() as $f)
                <x-ui.badge level="info">{{ $f }}</x-ui.badge>
              @endforeach
            </div>
          </div>
          <p class="ui:m-0 ui:text-sm ui:text-slate-600">{{ $check->description }}</p>
          <dl class="ui:m-0 ui:grid ui:gap-4 ui:text-sm ui:text-slate-700 ui:md:grid-cols-2">
            @if($check->rationale)
              <div>
                <dt class="{{ $dt }}">{{ __('Rationale') }}</dt>
                <dd class="ui:m-0 ui:mt-1">{{ $check->rationale }}</dd>
              </div>
            @endif
            @if($check->remediation)
              <div>
                <dt class="{{ $dt }}">{{ __('Remediation') }}</dt>
                <dd class="ui:m-0 ui:mt-1">{{ $check->remediation }}</dd>
              </div>
            @endif
            @if($check->references)
              <div>
                <dt class="{{ $dt }}">{{ __('References') }}</dt>
                <dd class="ui:m-0 ui:mt-1">
                  <ul class="ui:m-0 ui:pl-4">
                    @foreach($check->references as $reference)
                      @if(\Illuminate\Support\Str::startsWith($reference, ['http://', 'https://']))
                        <li class="ui:break-all"><a href="{{ $reference }}" target="_blank">{{ $reference }}</a></li>
                      @else
                        <li>{{ $reference }}</li>
                      @endif
                    @endforeach
                  </ul>
                </dd>
              </div>
            @endif
            @if($check->hasMitreTactics())
              <div>
                <dt class="{{ $dt }}">{{ __('Mitre Tactics') }}</dt>
                <dd class="ui:m-0 ui:mt-1 ui:flex ui:flex-wrap ui:gap-2">
                  @foreach($check->mitreTactics() as $tactic)
                    <a href="https://attack.mitre.org/tactics/{{ $tactic }}/" target="_blank">{{ $tactic }}</a>
                  @endforeach
                </dd>
              </div>
            @endif
            @if($check->hasMitreTechniques())
              <div>
                <dt class="{{ $dt }}">{{ __('Mitre Techniques') }}</dt>
                <dd class="ui:m-0 ui:mt-1 ui:flex ui:flex-wrap ui:gap-2">
                  @foreach($check->mitreTechniques() as $technique)
                    <a href="https://attack.mitre.org/techniques/{{ $technique }}/" target="_blank">{{ $technique }}</a>
                  @endforeach
                </dd>
              </div>
            @endif
            @if($check->hasMitreMitigations())
              <div>
                <dt class="{{ $dt }}">{{ __('Mitre Mitigations') }}</dt>
                <dd class="ui:m-0 ui:mt-1 ui:flex ui:flex-wrap ui:gap-2">
                  @foreach($check->mitreMitigations() as $mitigation)
                    <a href="https://attack.mitre.org/mitigations/{{ $mitigation }}/" target="_blank">{{ $mitigation }}</a>
                  @endforeach
                </dd>
              </div>
            @endif
          </dl>
          <pre class="ui:m-0 ui:overflow-auto ui:rounded-lg ui:bg-slate-50 ui:p-3 ui:text-xs ui:text-slate-700">{{ $check->rule }}</pre>
          @if($checkScripts->isNotEmpty())
            <div class="ui:flex ui:flex-wrap ui:items-center ui:gap-2">
              <span class="ui:text-sm ui:font-medium ui:text-slate-600">{{ __('Script') }}</span>
              @foreach($checkScripts as $s)
                <x-ui.button variant="secondary" size="sm" icon="download-simple" :href="$s['href']" download="{{ $s['name'] }}">
                  {{ $s['label'] }}
                </x-ui.button>
              @endforeach
            </div>
          @endif
        </div>
      </x-ui.card>
    @endforeach
  </div>
</x-layouts.app>
