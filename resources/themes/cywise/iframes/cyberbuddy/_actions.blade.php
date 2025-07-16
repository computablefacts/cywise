<style>

  .tw-actions {
    flex-grow: 1;
    overflow: hidden;
    width: 100%;
    height: 100%;
  }

  .tw-actions .tw-centered {
    align-items: center;
    justify-content: center;
    display: flex;
    height: 100%;
    padding: 0.5rem
  }

  .tw-actions .tw-centered .tw-grid {
    grid-template-columns: repeat(2, minmax(0px, 1fr));
    grid-template-rows: 160px 160px;
    display: grid;
    width: 100%;
    max-width: 48rem;
    gap: 16px
  }

</style>
<div class="tw-actions" style="display: none;">
  <div class="tw-centered">
    <div class="tw-grid">
      @include('theme::iframes.cyberbuddy._action', [
      'title' => __('Hardening'),
      'subtitle' => __('Durcissement des systèmes'),
      'text' => __('Qu\'est-ce que le hardening ?')
      ])
      @include('theme::iframes.cyberbuddy._action', [
      'title' => __('Mots de passe'),
      'subtitle' => __('Gestion des mots de passe'),
      'text' => __('Quel doit être la complexité d\'un mot de passe administrateur ?')
      ])
      @include('theme::iframes.cyberbuddy._action', [
      'title' => __('COMAR'),
      'subtitle' => __('Comité d\'architecture'),
      'text' => __('Que peux-tu me dire sur le comité d\'architecture ?')
      ])
      @include('theme::iframes.cyberbuddy._action', [
      'title' => __('USB'),
      'subtitle' => __('Gestion des clés USB'),
      'text' => __('L\'usage des clefs USB est-il autorisé ?')
      ])
    </div>
  </div>
</div>