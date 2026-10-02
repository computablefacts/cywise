{{--
  First visit: no asset, no server yet.

  Two ways to start, side by side:
    1. monitor a domain / IP  → reload: the dashboard switches to the overview
    2. install the server agent
--}}
@php
  $firstName = Str::before(trim(Auth::user()->name), ' ');
@endphp

<div class="ui:flex ui:flex-col ui:gap-8"
     x-data @asset-created.window="window.location.reload()">

  <x-ui.page-header :title="__('Welcome to Cywise, :name!', ['name' => $firstName])"
                    :subtitle="__('Add what you want to protect. Cywise takes care of the rest.')"/>

  <div class="ui:grid ui:gap-6 ui:lg:grid-cols-2">

    {{-- 1. Domain or IP --}}
    <x-ui.card class="ui:ring-2 ui:ring-brand-100 ui:border-brand-200!">
      <div class="ui:flex ui:h-full ui:flex-col ui:gap-5">
        <div class="ui:flex ui:items-start ui:gap-4">
          <span class="ui:flex ui:size-11 ui:shrink-0 ui:items-center ui:justify-center ui:rounded-xl ui:bg-brand-500 ui:text-white">
            <x-phosphor-globe-bold class="ui:size-5"/>
          </span>
          <div>
            <div class="ui:text-xs ui:font-semibold ui:uppercase ui:tracking-wider ui:text-brand-600">{{ __('Step :n', ['n' => 1]) }}</div>
            <h2 class="ui:m-0 ui:text-lg ui:font-semibold ui:text-ink">{{ __('Monitor a domain or an IP address') }}</h2>
            <p class="ui:m-0 ui:mt-1 ui:text-sm ui:text-slate-500">
              {{ __('Cywise scans your Internet-facing perimeter: vulnerabilities, exposed services and data leaks.') }}
            </p>
          </div>
        </div>

        {{-- What the user gets --}}
        <ul class="ui:m-0 ui:flex ui:list-none ui:flex-col ui:gap-2 ui:p-0">
          @foreach([
            __('Vulnerabilities and exposed services'),
            __('Leaked credentials of your employees'),
            __('Automatic monitoring of discovered subdomains'),
          ] as $benefit)
            <li class="ui:flex ui:items-center ui:gap-2 ui:text-sm ui:text-slate-700">
              <x-phosphor-check-circle-fill class="ui:size-4 ui:shrink-0 ui:text-brand-500"/>
              {{ $benefit }}
            </li>
          @endforeach
        </ul>

        <form x-data="{ asset: '', busy: false }"
              @submit.prevent="
                if (!asset.trim()) { return; }
                busy = true;
                createAssetApiCall(asset.trim(), true, () => {
                  window.toaster.toastSuccess(@js(__('The monitoring started.')));
                  window.dispatchEvent(new CustomEvent('asset-created'));
                });
                setTimeout(() => busy = false, 1000);
              "
              class="ui:m-0 ui:mt-auto ui:flex ui:flex-col ui:gap-2 ui:sm:flex-row">
          <label class="ui:m-0 ui:flex-1">
            <span class="ui:sr-only">{{ __('Domain or IP') }}</span>
            <input type="text" x-model="asset" autofocus
                   placeholder="{{ __('example.com or 93.184.215.14') }}"
                   class="ui:h-10 ui:w-full ui:rounded-lg ui:border ui:border-solid ui:border-line ui:bg-white ui:px-3 ui:text-sm ui:text-ink ui:placeholder:text-slate-400 ui:focus:border-brand-500 ui:focus:outline-none ui:focus:ring-2 ui:focus:ring-brand-100">
          </label>
          <x-ui.button type="submit" icon="arrow-right" x-bind:disabled="busy">{{ __('Monitor') }}</x-ui.button>
        </form>
      </div>
    </x-ui.card>

    {{-- 2. Server agent --}}
    <x-ui.card>
      <div class="ui:flex ui:h-full ui:flex-col ui:gap-5">
        <div class="ui:flex ui:items-start ui:gap-4">
          <span class="ui:flex ui:size-11 ui:shrink-0 ui:items-center ui:justify-center ui:rounded-xl ui:bg-ink ui:text-white">
            <x-phosphor-hard-drives-bold class="ui:size-5"/>
          </span>
          <div>
            <div class="ui:text-xs ui:font-semibold ui:uppercase ui:tracking-wider ui:text-slate-500">{{ __('Step :n', ['n' => 2]) }}</div>
            <h2 class="ui:m-0 ui:text-lg ui:font-semibold ui:text-ink">{{ __('Protect a server') }}</h2>
            <p class="ui:m-0 ui:mt-1 ui:text-sm ui:text-slate-500">
              {{ __('Install the Cywise agent to detect suspicious behaviors and audit the server configuration.') }}
            </p>
          </div>
        </div>

        @include('theme::iframes.dashboard._server-install')

        <div class="ui:mt-auto ui:flex ui:items-center ui:justify-between ui:gap-3 ui:rounded-lg ui:bg-slate-50 ui:px-3 ui:py-2">
          <span class="ui:text-xs ui:text-slate-500">{{ __('Your dashboard activates as soon as the server reports its first data.') }}</span>
          <x-ui.button variant="ghost" size="sm" icon="arrow-clockwise" onclick="window.location.reload()">{{ __('Refresh') }}</x-ui.button>
        </div>
      </div>
    </x-ui.card>
  </div>

  {{-- Meanwhile --}}
  @if(Auth::user()->canView('iframes.cyberbuddy'))
    <div class="ui:flex ui:flex-col ui:items-start ui:gap-4 ui:rounded-xl ui:border ui:border-solid ui:border-line ui:bg-white ui:p-5 ui:sm:flex-row ui:sm:items-center">
      <span class="ui:flex ui:size-11 ui:shrink-0 ui:items-center ui:justify-center ui:rounded-xl ui:bg-brand-50 ui:text-brand-500">
        <x-phosphor-robot-bold class="ui:size-5"/>
      </span>
      <div class="ui:flex-1">
        <h2 class="ui:m-0 ui:text-base ui:font-semibold ui:text-ink">{{ __('Do you have a question related to Cyber?') }}</h2>
        <p class="ui:m-0 ui:text-sm ui:text-slate-500">{{ __('Ask :name, your AI cybersecurity assistant.', ['name' => tenant_custom_text('CyberBuddy')]) }}</p>
      </div>
      <x-ui.button variant="secondary" :href="route('cyberbuddy')" icon="arrow-right">{{ __('Start a conversation') }}</x-ui.button>
    </div>
  @endif
</div>
