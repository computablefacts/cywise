<style>

  .terms {
    margin-bottom: var(--spacing-large);
    text-align: left;
    accent-color: var(--color-cywise);
    display: flex;
    align-items: center;
  }

  .terms label {
    margin-left: var(--spacing-medium);
  }

</style>
<h1>Sélection des sous-domaines</h1>
@include('theme::tools._loader', [ 'title' => 'La découverte de vos sous-domaines est en cours...', 'subtitle' => 'Compter environ 60 secondes' ])
<form action="{{ route('tools.cybercheck', [ 'hash' => $hash, 'step' => 3 ]) }}" method="post" class="hidden">
  @csrf
  <p class="msg">
    <!-- FILLED DYNAMICALLY -->
  </p>
  <p>Ne vous inquiétez pas, <b>l'audit est non intrusif et sans impact sur vos serveurs.</b></p>
  <div class="list">
    <!-- FILLED DYNAMICALLY -->
  </div>
  <div class="terms">
    <input type="checkbox" name="terms" checked>
    <label for="terms">Je certifie être propriétaire de ces domaines et autoriser Cywise à effectuer un test de
      sécurité sur les domaines sélectionnés.</label>
  </div>
  @include('theme::tools._errors')
  <div class="button-group">
    <button class="back-button" name="action" value="back">
      Retour
    </button>
    <button class="next-button-300p" name="action" value="next" type="submit">
      Suivant
    </button>
  </div>
</form>
<script>

  const elSubmitButton = document.querySelector('button[type="submit"]');
  const elTermsCheckbox = document.querySelector('input[type="checkbox"][name="terms"]');

  const toggleButtons = () => {

    const domains = Array.from(document.querySelectorAll('input[type="checkbox"][name^="d-"]:checked')).map(
      el => el.value);
    const isDomainSelected = domains.length > 0;
    const isTermsChecked = elTermsCheckbox.checked;
    const isReadyToSubmit = isDomainSelected && isTermsChecked;

    if (isReadyToSubmit) {
      elSubmitButton.classList.remove('disabled');
    } else {
      elSubmitButton.classList.add('disabled');
    }
    elSubmitButton.disabled = !isReadyToSubmit;

    let msg = '';

    if (!isDomainSelected) {
      msg += 'Veuillez sélectionner au moins un domaine.\n';
    }
    if (!isTermsChecked) {
      msg += 'Veuillez attester que vous êtes bien le propriétaire de ces domaines.\n';
    }
    if (msg.length > 0) {
      showErrors(msg);
    } else {
      hideErrors();
    }
  };

  toggleButtons();

  elTermsCheckbox.addEventListener('change', toggleButtons);

  fetch("{{ route('tools.discovery', [ 'hash' => $hash ]) }}", {
    method: 'POST', headers: {
      'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}',
    }, body: JSON.stringify({'domain': '{{ $trial->domain }}'}),
  }).then(response => {
    if (!response.ok) {
      throw new Error('Erreur réseau');
    }
    return response.json();
  })
  .then(domains => {

    // Set the message
    const elMessage = document.querySelector('.msg');
    if (domains.length === 0) {
      elMessage.innerText = "Aïe ! Je n'ai trouvé aucun sous-domaine associé à votre domaine. Revenez à l'étape précédente pour essayer avec un autre de vos domaines.";
    } else if (domains.length === 1) {
      elMessage.innerText = "Super, je vois que nous avons trouvé un sous-domaine associé à votre domaine.";
    } else {
      elMessage.innerText = "Super, je vois que nous avons trouvé plusieurs sous-domaines associés à votre domaine. Décochez ceux que vous ne souhaitez pas inclure dans l'audit.";
    }

    // Set the list of domains
    const elDomains = document.querySelector('.list');
    domains.sort().forEach((domain, idx) => {

      const elDomain = document.createElement('div');
      elDomain.classList.add('list-item');
      elDomain.innerHTML = `
        <input type="checkbox" name="d-${idx}" value="${domain}" checked>
        <label for="d1-${idx}">${domain}</label>
      `;
      elDomains.appendChild(elDomain);
    });

    // Capture the change event
    document.querySelectorAll('input[type="checkbox"][name^="d-"]:checked').forEach(
      el => el.addEventListener('change', toggleButtons));

    // Hide the loader
    const elLoader = document.querySelector('.loader-container');
    elLoader.classList.add('hidden');

    // Display the list of domains
    const elForm = document.querySelector('form');
    elForm.classList.remove('hidden');

    // Update submit button state
    toggleButtons();
  })
  .catch(error => console.error('Error:', error));

</script>
