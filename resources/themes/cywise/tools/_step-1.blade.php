<h1>Analyse de votre domaine</h1>
<p>Bonjour, je suis CyberBuddy, votre assistant en cybersécurité ! C'est moi qui vais vous accompagner tout au long de
  votre analyse. Entrez simplement le nom de domaine que vous souhaitez auditer. Je vais m'occuper du reste pour
  détecter les failles potentielles.</p>
<p><b>Pas besoin d'être un expert, je suis là pour vous guider !</b></p>
<p>On se lance ?!</p>
<form action="{{ route('tools.cybercheck', [ 'hash' => $hash, 'step' => 2 ]) }}" method="post">
  @csrf
  <input type="text" placeholder="Votre nom de domaine ex. cywise.io" name="domain" value="{{ $trial->domain }}">
  <button type="submit" name="action" value="next">Suivant →</button>
</form>
<script>

  const elInput = document.querySelector('input[name="domain"]');
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
