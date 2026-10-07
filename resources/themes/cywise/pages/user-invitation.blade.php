<?php

use App\Http\Middleware\CheckPermissionsHttpRequest;
use App\Http\Middleware\LogHttpRequests;
use function Laravel\Folio\{middleware, name};

middleware([LogHttpRequests::class, 'auth', CheckPermissionsHttpRequest::class]);
name('user-invitation');
?>

<x-layouts.app>
  <div class="ui:mx-auto ui:flex ui:w-full ui:max-w-2xl ui:flex-col ui:gap-6 ui:px-4 ui:py-8 ui:lg:px-8">

    <x-ui.page-header :title="__('Send an invitation')"
                      :subtitle="__('Enter the email address of the person invited to join your team below.')"/>

    {{-- Invitation form: submitted by the script below (JSON-RPC, no page reload) --}}
    <x-ui.card>
      <div class="ui:flex ui:flex-col ui:gap-4">
        <x-ui.field :label="__('Email')" for="email">
          <x-ui.input id="email" type="email" placeholder="jane.doe@example.com"/>
        </x-ui.field>
        <div class="ui:flex ui:justify-end">
          <x-ui.button id="create-invitation" icon="paper-plane-right">{{ __('Send') }}</x-ui.button>
        </div>
      </div>
    </x-ui.card>
  </div>
  <script>

    const btnCreate = document.querySelector('#create-invitation');
    const elEmail = document.querySelector('#email');

    btnCreate.addEventListener('click', () => {
      createUserInvitationApiCall(elEmail.value,
        () => window.toaster.toastSuccess("{{ __('The invitation has been sent.') }}"));
    });

  </script>
</x-layouts.app>

