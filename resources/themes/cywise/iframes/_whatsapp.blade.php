{{--
  WhatsApp setup: the user creates a Meta Business app, saves its phone number id and access token,
  then declares the webhook returned by Cywise in the Meta app.
--}}
@php
  $meta = '<a href="https://developers.facebook.com/" target="_blank" rel="noopener" class="ui:font-medium ui:text-brand-600! ui:no-underline! ui:hover:underline!">Meta for Developers</a>';
@endphp

<div x-data="whatsappSetup(@js(Auth::user()->whatsapp_phone_number_id ?? ''), @js(Auth::user()->whatsapp_access_token ?? ''))"
     class="ui:flex ui:flex-col ui:gap-5">

  <div class="ui:flex ui:items-center ui:justify-between ui:gap-3">
    <p class="ui:m-0 ui:text-sm ui:text-slate-600">{{ __('Connect a WhatsApp Business number to Cywise.') }}</p>
    <x-ui.badge level="low" x-show="webhook" x-cloak>{{ __('Configuration saved') }}</x-ui.badge>
  </div>

  <x-ui.steps :steps="[__('Create a Meta app'), __('Save its configuration in Cywise'), __('Declare the webhook to Meta'), __('Chat on WhatsApp')]">
    <x-slot:step1>
      <p class="ui:m-0">
        {!! __('On :meta, create a "Business" app and add the WhatsApp product. Copy its phone number ID and an access token.', ['meta' => $meta]) !!}
      </p>
    </x-slot:step1>

    <x-slot:step2>
      <form @submit.prevent="save()" class="ui:m-0 ui:flex ui:flex-col ui:gap-3">
        <x-ui.field :label="__('Phone number ID')" for="wa-phone-number-id">
          <x-ui.input id="wa-phone-number-id" x-model="phoneNumberId" placeholder="106XXXXXXXXXXXX"/>
        </x-ui.field>
        <x-ui.field :label="__('Access token')" for="wa-access-token">
          <x-ui.input id="wa-access-token" x-model="accessToken" placeholder="EAAB…"/>
        </x-ui.field>
        <x-ui.button type="submit" class="ui:w-fit" x-bind:disabled="saving">{{ __('Save') }}</x-ui.button>
      </form>
    </x-slot:step2>

    <x-slot:step3>
      <p class="ui:m-0">{{ __('In the WhatsApp settings of the Meta app (Configuration, Webhook), use these values:') }}</p>
      <x-ui.field :label="__('Callback URL')">
        <x-ui.copy-field bind="webhook" :placeholder="__('Save the configuration first.')"/>
      </x-ui.field>
      <x-ui.field :label="__('Verify token')">
        <x-ui.copy-field bind="verifyToken" :placeholder="__('Save the configuration first.')"/>
      </x-ui.field>
      <p class="ui:m-0 ui:text-xs ui:text-slate-500">{!! __('Then subscribe to the :field field.', ['field' => '<b>messages</b>']) !!}</p>
    </x-slot:step3>

    <x-slot:step4>
      <p class="ui:m-0">
        {{ __('Once Meta has validated the webhook, send a WhatsApp message to your number: it answers with :name.', ['name' => tenant_custom_text('CyberBuddy')]) }}
      </p>
    </x-slot:step4>
  </x-ui.steps>
</div>

<script>
  // State of the WhatsApp setup (iframes/_whatsapp): fields typed, webhook and verify token returned by Cywise
  function whatsappSetup(phoneNumberId, accessToken) {
    return {
      phoneNumberId: phoneNumberId,
      accessToken: accessToken,
      webhook: '',
      verifyToken: '',
      saving: false,

      init() {
        getWhatsAppConfigurationApiCall(result => {
          this.webhook = result.webhook ?? '';
          this.verifyToken = result.verify_token ?? '';
        });
      },

      save() {
        const phoneNumberId = this.phoneNumberId.trim();
        const accessToken = this.accessToken.trim();

        if (!phoneNumberId || !accessToken) {
          window.toaster.toastError(@js(__('Enter the phone number ID and the access token.')));
          return;
        }

        this.saving = true;
        setWhatsAppConfigurationApiCall(accessToken, phoneNumberId, (result) => {
          this.webhook = result.webhook ?? '';
          this.verifyToken = result.verify_token ?? '';
          window.toaster.toastSuccess(@js(__('Configuration saved. Now declare the webhook to Meta.')));
        }, () => this.saving = false);
      },
    };
  }
</script>
