{{--
  Telegram bot setup: the user creates a bot, saves its token, then declares the Cywise webhook to Telegram.
  The curl commands use the saved token (not the one being typed).
--}}
@php
  $code = 'ui:rounded ui:bg-slate-100 ui:px-1.5 ui:py-0.5 ui:font-mono ui:text-xs ui:text-ink';
@endphp

<div x-data="telegramSetup(@js(Auth::user()->telegram_bot_token ?? ''))" class="ui:flex ui:flex-col ui:gap-5">

  <div class="ui:flex ui:items-center ui:justify-between ui:gap-3">
    <p class="ui:m-0 ui:text-sm ui:text-slate-600">{{ __('Create your own Telegram bot, then link it to Cywise.') }}</p>
    <x-ui.badge level="low" x-show="webhook" x-cloak>{{ __('Token saved') }}</x-ui.badge>
  </div>

  <x-ui.steps :steps="[__('Create a bot'), __('Save its token in Cywise'), __('Declare the webhook to Telegram'), __('Chat with your bot')]">
    <x-slot:step1>
      <p class="ui:m-0">
        {!! __('In Telegram, open a conversation with :botfather and send :command.', ['botfather' => "<code class=\"{$code}\">@BotFather</code>", 'command' => "<code class=\"{$code}\">/newbot</code>"]) !!}
      </p>
      <p class="ui:m-0">{{ __('Follow the instructions (name, then an identifier ending with "bot"). BotFather then gives you an API token.') }}</p>
    </x-slot:step1>

    <x-slot:step2>
      <form @submit.prevent="save()" class="ui:m-0 ui:flex ui:flex-col ui:gap-2 ui:sm:flex-row">
        <x-ui.input x-model="token" placeholder="1234567890:ABCDEF…" aria-label="{{ __('Telegram bot token') }}"/>
        <x-ui.button type="submit" x-bind:disabled="saving">{{ __('Save') }}</x-ui.button>
      </form>
    </x-slot:step2>

    <x-slot:step3>
      <p class="ui:m-0">{{ __('Run this command once to send Telegram messages to Cywise:') }}</p>
      <x-ui.copy-field bind="setWebhookCommand" :placeholder="__('Save the token first.')"/>
      <p class="ui:m-0 ui:text-xs ui:text-slate-500">{{ __('To check the configuration:') }}</p>
      <x-ui.copy-field bind="webhookInfoCommand" :placeholder="__('Save the token first.')"/>
    </x-slot:step3>

    <x-slot:step4>
      <p class="ui:m-0">
        {{ __('Send a message to your bot: it answers with :name, in the context of your Cywise account.', ['name' => tenant_custom_text('CyberBuddy')]) }}
      </p>
    </x-slot:step4>
  </x-ui.steps>
</div>

<script>
  // State of the Telegram setup (iframes/_telegram): token typed, token saved, webhook returned by Cywise
  function telegramSetup(token) {
    return {
      token: token,
      savedToken: '',
      webhook: '',
      saving: false,

      get setWebhookCommand() {
        if (!this.savedToken || !this.webhook) {
          return '';
        }
        return `curl -s "https://api.telegram.org/bot${this.savedToken}/setWebhook" -d url=${this.webhook}`;
      },

      get webhookInfoCommand() {
        if (!this.savedToken) {
          return '';
        }
        return `curl -s https://api.telegram.org/bot${this.savedToken}/getWebhookInfo | jq`;
      },

      init() {
        getTelegramConfigurationApiCall(result => {
          this.savedToken = result.bot_token ?? '';
          this.webhook = result.webhook ?? '';
        });
      },

      save() {
        const token = this.token.trim();

        if (!token) {
          window.toaster.toastError(@js(__('Enter the token given by BotFather.')));
          return;
        }

        this.saving = true;
        setTelegramConfigurationApiCall(token, (result) => {
          this.savedToken = token;
          this.webhook = result.webhook ?? '';
          window.toaster.toastSuccess(@js(__('Token saved. Now declare the webhook to Telegram.')));
        }, () => this.saving = false);
      },
    };
  }
</script>
