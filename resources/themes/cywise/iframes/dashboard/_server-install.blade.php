{{-- Agent install command, per platform. Same commands as iframes/_agent. --}}
@php
  $token = Auth::user()->sentinelApiToken();
  $commands = [
    'linux' => "curl -s \"" . app_url() . "/setup/script?api_token={$token}&server_ip=$(curl -s ipinfo.io | jq -r '.ip')&server_name=$(hostname)\" | bash",
    'windows' => "Invoke-WebRequest -Uri \"" . app_url() . "/setup/script?api_token={$token}&server_ip=$((Invoke-RestMethod -Uri 'https://ipinfo.io').ip)&server_name=$(\$env:COMPUTERNAME)&platform=windows\" -UseBasicParsing | Invoke-Expression",
  ];
@endphp

<div x-data="{ platform: 'linux' }" class="ui:flex ui:flex-col ui:gap-3">
  <div class="ui:inline-flex ui:self-start ui:rounded-lg ui:bg-slate-100 ui:p-1" role="tablist">
    @foreach(['linux' => __('Linux'), 'windows' => __('Windows')] as $key => $label)
      <button type="button" role="tab" @click="platform = '{{ $key }}'" :aria-selected="platform === '{{ $key }}'"
              :class="platform === '{{ $key }}' ? 'ui:bg-white ui:text-ink ui:shadow-xs' : 'ui:bg-transparent ui:text-slate-500 ui:hover:text-ink'"
              class="ui:rounded-md ui:border-0 ui:px-3 ui:py-1.5 ui:text-sm ui:font-medium ui:cursor-pointer">
        {{ $label }}
      </button>
    @endforeach
    <span class="ui:px-3 ui:py-1.5 ui:text-sm ui:font-medium ui:text-slate-300" title="{{ __('Coming soon') }}">{{ __('MacOS') }}</span>
  </div>

  <p x-show="platform === 'linux'" class="ui:m-0 ui:text-sm ui:text-slate-600">
    {{ __('To monitor a new Linux server, log in as root and execute this command line:') }}
  </p>
  <p x-show="platform === 'windows'" x-cloak class="ui:m-0 ui:text-sm ui:text-slate-600">
    {{ __('To monitor a new Windows server, log in as administrator and execute this command line:') }}
  </p>

  <x-ui.copy-field x-show="platform === 'linux'" :value="$commands['linux']"/>
  <x-ui.copy-field x-show="platform === 'windows'" x-cloak :value="$commands['windows']"/>
</div>
