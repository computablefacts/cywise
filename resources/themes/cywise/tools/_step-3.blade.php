<h1>Configuration de vos honeypots</h1>
<p>Une petite seconde, je configure maintenant vos honeypots. Les honeypots agissent comme des leurres pour attirer les
  cyber-attaquants et analyser leur comportement. <b>Pas d'inquiétude, tout est isolé et sécurisé !</b></p>
<p>Si vous décidez d'aller plus loin, la solution Cywise vous offre la possibilité de configurer des honeypots sur vos
  propres domaines et sous-domaines !</p>
@include('theme::tools._loader', [ 'title' => 'La création de vos honeypots est en cours...', 'subtitle' => 'Compter environ 60 secondes' ])
<form action="{{ route('tools.cybercheck', [ 'hash' => $hash, 'step' => 4 ]) }}" method="post">
  @csrf
  <div class="button-group">
    <button class="back-button" name="action" value="back">
      Retour
    </button>
    <button class="next-button-300p disabled" name="action" value="next" type="submit" disabled>
      Suivant
    </button>
  </div>
</form>
<script>
  const setCheckmark = () => {

    const elLoader = document.querySelector('.loader-picto');
    elLoader.classList.remove('loader-picto');
    elLoader.classList.add('checkmark');

    const elTitle = document.querySelector('.loader-info h2');
    elTitle.innerText = "Vos honeypots sont prêts!"

    const elText = document.querySelector('.loader-info p');
    elText.remove();

    const elSubmitButton = document.querySelector('button[type="submit"]');
    elSubmitButton.disabled = false;
    elSubmitButton.classList.remove('disabled');
  };
</script>
@if($trial->honeypots)
<script>
  setCheckmark();
</script>
@else
<script>
  setTimeout(setCheckmark, 3000);
</script>
@endif
