<?php

use App\Http\Controllers\Iframes\UsersController;
use App\Http\Middleware\CheckPermissionsHttpRequest;
use App\Http\Middleware\LogHttpRequests;
use Illuminate\Http\Request;
use function Laravel\Folio\{middleware, name, render};

middleware([LogHttpRequests::class, 'auth', CheckPermissionsHttpRequest::class]);
name('users');
render(function (Request $request) {
  return app(UsersController::class)($request);
});
?>

<x-layouts.app>
  <div class="ui:mx-auto ui:flex ui:w-full ui:max-w-7xl ui:flex-col ui:gap-6 ui:px-4 ui:py-8 ui:lg:px-8">

    <x-ui.page-header :title="__('Users')" :subtitle="__('Members of your team, their roles and audit reports.')">
      <x-slot:actions>
        <x-ui.button icon="plus" :href="route('user-invitation')">{{ __('Invite a user') }}</x-ui.button>
      </x-slot:actions>
    </x-ui.page-header>

    <x-ui.card flush>
      @if($users->isEmpty())
        <x-ui.empty icon="users">{{ __('None.') }}</x-ui.empty>
      @else
        <x-ui.table>
          <thead>
          <tr>
            <th>{{ __('Name') }}</th>
            <th>{{ __('Username') }}</th>
            <th>{{ __('Email') }}</th>
            <th class="ui:text-center!">{{ __('Audit Report') }}</th>
            <th class="ui:text-right!">{{ __('Send audit report') }}</th>
          </tr>
          </thead>
          <tbody>
          @foreach($users as $user)
            {{-- Roles shown under the name: one row per user --}}
            <tr>
              <td>
                <div class="ui:flex ui:flex-col ui:gap-1.5">
                  <span class="ui:font-semibold">{{ isset($user->fullname) ? $user->fullname : $user->name }}</span>
                  <div class="ui:flex ui:flex-wrap ui:gap-1">
                    @foreach(collect($user->roles->all())->sortBy('name') as $role)
                      <x-ui.badge level="info">{{ $role->name }}</x-ui.badge>
                    @endforeach
                  </div>
                </div>
              </td>
              <td class="ui:text-slate-600">
                {{ isset($user->username) ? $user->username : '' }}
              </td>
              <td>
                <a href="mailto:{{ $user->email }}" target="_blank" class="ui:text-brand-600! ui:no-underline! ui:hover:underline!">
                  {{ $user->email }}
                </a>
              </td>
              <td class="ui:text-center">
                <input type="checkbox" class="toggle-gets-audit-report ui:size-4 ui:cursor-pointer ui:accent-brand-500"
                       data-user-id="{{ $user->id }}" {{ $user->gets_audit_report ? 'checked' : '' }}>
              </td>
              <td class="ui:text-right">
                <x-ui.button variant="secondary" size="sm" class="send-audit-report" data-user-id="{{ $user->id }}">
                  {{ __('Send') }}
                </x-ui.button>
              </td>
            </tr>
          @endforeach
          </tbody>
        </x-ui.table>
      @endif
    </x-ui.card>
  </div>
  <script>

    document.querySelectorAll('.toggle-gets-audit-report').forEach((checkbox) => {
      checkbox.addEventListener('change',
        (event) => toggleGetsAuditReportApiCall(event.target.getAttribute('data-user-id'),
          response => window.toaster.toastSuccess(response.msg)));
    });

    document.querySelectorAll('.send-audit-report').forEach((button) => {
      button.addEventListener('click',
        (event) => {
          const userId = event.currentTarget.getAttribute('data-user-id');
          sendAuditReportApiCall(userId, response => window.toaster.toastSuccess(response.msg));
        });
    });

  </script>
</x-layouts.app>

