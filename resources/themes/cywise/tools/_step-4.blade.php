<style>

</style>
<h1>Récupération des résultats</h1>
<p>Je vous invite maintenant à entrer votre adresse e-mail pour recevoir les résultats détaillés de l'audit. <b>Vous
  pourrez consulter les vulnérabilités identifiées et mes recommandations (gratuites) pour vous aider à renforcer votre
    sécurité.</b></p>
<p>Votre adresse e-mail ne sera jamais utilisée à des fins commerciales. Elle sert uniquement à vous transmettre le
  rapport.</p>
<form action="{{ route('tools.cybercheck', [ 'hash' => $hash, 'step' => 5 ]) }}" method="post">
  @csrf
  <input type="email" placeholder="Votre adresse email ex. j.doe@cywise.io" name="email" value="{{ $trial->email }}">
  <button class="next-button-100p" name="action" value="next" type="submit">
    Lancer le test
  </button>
  <button class="back-button" name="action" value="back">
    Retour
  </button>
</form>
<script>

  const elInput = document.querySelector('input[name="email"]');
  const elSubmitButton = document.querySelector('button[type="submit"]');

  const toggleButtonState = () => {
    const isInputEmpty = elInput.value.trim() === '';
    if (isInputEmpty) {
      elSubmitButton.classList.add('disabled');
    } else {
      elSubmitButton.classList.remove('disabled');
    }
    elSubmitButton.disabled = isInputEmpty;
  };

  toggleButtonState();

  elInput.addEventListener('input', toggleButtonState);

</script>
