<?php

use App\Http\Controllers\Iframes\ActionsController;
use App\Http\Middleware\CheckPermissionsHttpRequest;
use App\Http\Middleware\LogHttpRequests;
use Illuminate\Http\Request;
use function Laravel\Folio\{middleware, name, render};

middleware([LogHttpRequests::class, 'auth', CheckPermissionsHttpRequest::class]);
name('actions');
render(function (Request $request) {
  return app(ActionsController::class)($request);
});
?>

<x-layouts.app>

  @php
  $me = Auth::user();
  @endphp

  <div x-data="{ tab: '{{ $userSelected ? 'user' : 'tenant' }}' }"
       class="ui:mx-auto ui:flex ui:w-full ui:max-w-5xl ui:flex-col ui:gap-6 ui:px-4 ui:py-8 ui:lg:px-8">

    <x-ui.page-header :title="__('Actions')"
                      :subtitle="__('Actions CyberBuddy may run, for everyone or per user.')">
      <x-slot:actions>
        <x-ui.button icon="plus" :href="route('actions.editor')">{{ __('New action') }}</x-ui.button>
      </x-slot:actions>
    </x-ui.page-header>

    {{-- Tabs: tenant defaults / per user overrides --}}
    <div class="ui:inline-flex ui:self-start ui:rounded-lg ui:bg-slate-100 ui:p-1" role="tablist">
      @foreach(['tenant' => [0, __('Actions for all')], 'user' => [1, __('User overrides')]] as $key => [$index, $label])
        <button type="button" role="tab" id="simple-tab-{{ $index }}" @click="tab = '{{ $key }}'" :aria-selected="tab === '{{ $key }}'"
                :class="tab === '{{ $key }}' ? 'ui:bg-white ui:text-ink ui:shadow-xs' : 'ui:bg-transparent ui:text-slate-500 ui:hover:text-ink'"
                class="ui:rounded-md ui:border-0 ui:px-3 ui:py-1.5 ui:text-sm ui:font-medium ui:cursor-pointer">
          {{ $label }}
        </button>
      @endforeach
    </div>

    {{-- Tenant defaults --}}
    <div id="tab-tenant" role="tabpanel" x-show="tab === 'tenant'" @if($userSelected) x-cloak @endif>
      <form id="form-tenant" method="post" action="#">
        @csrf
        <input type="hidden" name="scope_type" value="tenant">
        <input type="hidden" name="scope_id" value="{{ $me->tenant_id }}">
        <x-ui.card flush>
          <ul class="ui:m-0 ui:list-none ui:p-0">
            @foreach($actions as $actionName => $instance)
              @php
              $checked = ($tenantSettings[$actionName]->enabled ?? true);
              @endphp
              {{-- [x] name [remote]  /  description --}}
              <li class="ui:flex ui:flex-col ui:gap-2 ui:border-0 ui:border-t ui:border-solid ui:border-line ui:px-5 ui:py-4 ui:first:border-t-0">
                <label class="ui:m-0 ui:inline-flex ui:items-center ui:gap-2 ui:cursor-pointer">
                  <input type="checkbox"
                         name="actions[]"
                         value="{{ $actionName }}"
                         class="ui:size-4 ui:cursor-pointer ui:accent-brand-500"
                         @checked($checked)>
                  @if($instance->isRemote())
                    <a href="{{ route('actions.editor', ['action_id' => $instance->id()]) }}"
                       class="ui:text-sm ui:font-semibold ui:text-ink! ui:no-underline! ui:hover:text-brand-600!">
                      {{ $instance->name() }}
                    </a>
                    <x-ui.badge level="info">{{ __('remote') }}</x-ui.badge>
                  @else
                    <span class="ui:text-sm ui:font-semibold ui:text-ink">{{ $instance->name() }}</span>
                  @endif
                </label>
                <pre class="ui:m-0 ui:whitespace-pre-wrap ui:break-words ui:rounded-lg ui:bg-slate-50 ui:p-3 ui:font-sans ui:text-xs ui:text-slate-600">{{ \Str::trim($instance->description()) }}</pre>
              </li>
            @endforeach
          </ul>
          <div class="ui:flex ui:justify-end ui:border-0 ui:border-t ui:border-solid ui:border-line ui:px-5 ui:py-3">
            <x-ui.button type="submit">{{ __('Save') }}</x-ui.button>
          </div>
        </x-ui.card>
      </form>
    </div>

    {{-- Per user overrides: unset actions fall back to the tenant defaults --}}
    <div id="tab-user" role="tabpanel" x-show="tab === 'user'" @unless($userSelected) x-cloak @endunless
         class="ui:flex ui:flex-col ui:gap-4">
      <x-ui.card>
        <form method="get" action="{{ route('actions') }}">
          <x-ui.field :label="__('Select a user')" for="user_id">
            <x-ui.select id="user_id" name="user_id" onchange="this.form.submit()">
              <option value="">— {{ __('None') }} —</option>
              @foreach($users as $u)
                <option value="{{ $u->id }}" @selected(optional($userSelected)->id === $u->id)>
                  {{ $u->name }} ({{ $u->email }})
                </option>
              @endforeach
            </x-ui.select>
          </x-ui.field>
        </form>
      </x-ui.card>

      @if($userSelected)
        <form id="form-user" method="post" action="#">
          @csrf
          <input type="hidden" name="scope_type" value="user">
          <input type="hidden" name="scope_id" value="{{ $userSelected->id }}">
          <x-ui.card flush>
            <ul class="ui:m-0 ui:list-none ui:p-0">
              @foreach($actions as $actionName => $instance)
                @php
                $checked = ($userSettings[$actionName]->enabled ?? null);
                $checked = $checked === null ? ($tenantSettings[$actionName]->enabled ?? true) : $checked;
                @endphp
                {{-- [x] name [remote]  /  description --}}
                <li class="ui:flex ui:flex-col ui:gap-2 ui:border-0 ui:border-t ui:border-solid ui:border-line ui:px-5 ui:py-4 ui:first:border-t-0">
                  <label class="ui:m-0 ui:inline-flex ui:items-center ui:gap-2 ui:cursor-pointer">
                    <input type="checkbox"
                           name="actions[]"
                           value="{{ $actionName }}"
                           class="ui:size-4 ui:cursor-pointer ui:accent-brand-500"
                           @checked($checked)>
                    @if($instance->isRemote())
                      <a href="{{ route('actions.editor', ['action_id' => $instance->id()]) }}"
                         class="ui:text-sm ui:font-semibold ui:text-ink! ui:no-underline! ui:hover:text-brand-600!">
                        {{ $instance->name() }}
                      </a>
                      <x-ui.badge level="info">{{ __('remote') }}</x-ui.badge>
                    @else
                      <span class="ui:text-sm ui:font-semibold ui:text-ink">{{ $instance->name() }}</span>
                    @endif
                  </label>
                  <pre class="ui:m-0 ui:whitespace-pre-wrap ui:break-words ui:rounded-lg ui:bg-slate-50 ui:p-3 ui:font-sans ui:text-xs ui:text-slate-600">{{ \Str::trim($instance->description()) }}</pre>
                </li>
              @endforeach
            </ul>
            <div class="ui:flex ui:justify-end ui:border-0 ui:border-t ui:border-solid ui:border-line ui:px-5 ui:py-3">
              <x-ui.button type="submit">{{ __('Save') }}</x-ui.button>
            </div>
          </x-ui.card>
        </form>
      @else
        <x-ui.card>
          <x-ui.empty icon="user">{{ __('Select a user to override its defaults parameters.') }}</x-ui.empty>
        </x-ui.card>
      @endif
    </div>
  </div>
  <script>

    function handleSubmit(form) {
      form.addEventListener('submit', (e) => {

        e.preventDefault();

        const scopeType = form.querySelector('input[name="scope_type"]').value;
        const scopeId = parseInt(form.querySelector('input[name="scope_id"]').value, 10);
        const actions = Array.from(form.querySelectorAll('input[name="actions[]"]:checked')).map(el => el.value);

        saveRemoteActionsSettingsApiCall(scopeType, scopeId, actions);
      });
    }

    const elFormTenant = document.getElementById('form-tenant');

    if (elFormTenant) {
      handleSubmit(elFormTenant);
    }

    const elFormUser = document.getElementById('form-user');

    if (elFormUser) {
      handleSubmit(elFormUser);
    }

  </script>
</x-layouts.app>
