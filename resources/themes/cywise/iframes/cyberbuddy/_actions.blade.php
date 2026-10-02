{{-- Empty conversation: greeting + suggestions. Shown/hidden by the CyberBuddy page JS (.tw-actions). --}}
<div class="tw-actions" style="display: none;">
  <div class="ui:mx-auto ui:flex ui:max-w-3xl ui:flex-col ui:gap-6 ui:py-4">

    <div class="ui:flex ui:items-center ui:gap-5 ui:rounded-2xl ui:bg-brand-50 ui:p-6">
      <span class="ui:flex ui:size-14 ui:shrink-0 ui:items-center ui:justify-center ui:rounded-2xl ui:bg-white ui:text-brand-500 ui:shadow-xs">
        <x-phosphor-robot-bold class="ui:size-7"/>
      </span>
      <div>
        <h2 class="ui:m-0 ui:text-xl ui:font-bold ui:text-ink">{{ __('Hello!') }}</h2>
        <p class="ui:m-0 ui:mt-1 ui:text-sm ui:text-slate-600">
          {{ __('I am :name, your AI cybersecurity assistant. Ask me a question or pick a suggestion below.', ['name' => tenant_custom_text('CyberBuddy')]) }}
        </p>
      </div>
    </div>

    <div class="ui:grid ui:gap-4 ui:sm:grid-cols-2">
      @include('theme::iframes.cyberbuddy._action', [
      'icon' => 'shield-check',
      'title' => __('Hardening'),
      'subtitle' => __('Durcissement des systèmes'),
      'text' => __('Qu\'est-ce que le durcissement en cybersécurité ?')
      ])
      @include('theme::iframes.cyberbuddy._action', [
      'icon' => 'key',
      'title' => __('Mots de passe'),
      'subtitle' => __('Gestion des mots de passe'),
      'text' => __('Quel doit être la complexité d\'un mot de passe administrateur ?')
      ])
      @include('theme::iframes.cyberbuddy._action', [
      'icon' => 'paper-plane-tilt',
      'title' => __('WeTransfer'),
      'subtitle' => __('Envoi de fichiers'),
      'text' => __('Est-il sécurisé de partager un fichier par WeTransfer ?')
      ])
      @include('theme::iframes.cyberbuddy._action', [
      'icon' => 'usb',
      'title' => __('USB'),
      'subtitle' => __('Gestion des clés USB'),
      'text' => __('L\'usage des clefs USB est-il autorisé ?')
      ])
    </div>
  </div>
</div>
