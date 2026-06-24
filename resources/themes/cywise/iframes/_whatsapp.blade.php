<p class="mb-2">
  Pour utiliser Cywise depuis WhatsApp, vous devez configurer une application sur le portail <a
      href="https://developers.facebook.com/" target="_blank">Meta for Developers</a>.
</p>
<p>
  <b>1. Configurer l'application WhatsApp</b>
</p>
<p>
  Créez une application de type "Business" sur <a href="https://developers.facebook.com/" target="_blank">Meta for
    Developers</a>, ajoutez le produit "WhatsApp" et récupérez votre <b>ID de numéro de téléphone</b> et un <b>Token
    d'accès temporaire</b> (ou permanent).
</p>
<p>
  <b>2. Enregistrer la configuration auprès de Cywise</b>
</p>
<div class="mb-3">
  <label for="wa-phone-number-id" class="form-label">ID de numéro de téléphone</label>
  <input id="wa-phone-number-id" type="text" class="form-control" placeholder="106XXXXXXXXXXXX"
         value="{{ Auth::user()->whatsapp_phone_number_id ?? '' }}">
</div>
<div class="mb-3">
  <label for="wa-access-token" class="form-label">Token d'accès (EAAB...)</label>
  <textarea id="wa-access-token" class="form-control" rows="3" placeholder="EAAB...">{{ Auth::user()->whatsapp_access_token ?? '' }}</textarea>
</div>
<button id="wa-save-config" class="btn btn-primary mb-3">
  <span class="bp4-icon bp4-icon-tick"></span>
  Enregistrer la configuration
</button>
<div id="wa-webhook-setup" style="display: none;">
  <p>
    <b>3. Configurer le webhook côté Meta</b>
  </p>
  <p>
    Dans la configuration WhatsApp de votre application Meta, allez dans "Configuration" (Webhook) et utilisez les
    valeurs suivantes :
  </p>
  <div class="mb-2">
    <label class="form-label">Callback URL</label>
    <div class="input-group">
      <input type="text" id="wa-webhook-url" class="form-control" readonly>
      <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('wa-webhook-url')">Copier
      </button>
    </div>
  </div>
  <div class="mb-3">
    <label class="form-label">Verify Token</label>
    <div class="input-group">
      <input type="text" id="wa-verify-token" class="form-control" readonly>
      <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('wa-verify-token')">Copier
      </button>
    </div>
  </div>
  <p class="small text-muted">
    N'oubliez pas de vous abonner aux champs <b>messages</b> dans les paramètres Webhook de l'application Meta.
  </p>
</div>
<p>
  <b>4. C'est fini !</b>
</p>
<p>
  Une fois le webhook validé par Meta, envoyez un message WhatsApp à votre numéro : Cywise vous répondra en
  utilisant <a
      href="{{ route('cyberbuddy') }}">{{ tenant_custom_text('CyberBuddy') }}</a>.
</p>

@push('scripts')
<script>
  function copyToClipboard(id) {
    const el = document.getElementById(id);
    el.select();
    document.execCommand('copy');
    window.toaster.toastSuccess("Copié dans le presse-papier");
  }

  (function () {
    const elBtn = document.getElementById('wa-save-config');
    const elPhoneId = document.getElementById('wa-phone-number-id');
    const elToken = document.getElementById('wa-access-token');
    const elWebhookSetup = document.getElementById('wa-webhook-setup');
    const elWebhookUrl = document.getElementById('wa-webhook-url');
    const elVerifyToken = document.getElementById('wa-verify-token');

    function updateWebhookOutputs(webhook, verifyToken) {
      if (webhook && verifyToken) {
        elWebhookUrl.value = webhook;
        elVerifyToken.value = verifyToken;
        elWebhookSetup.style.display = 'block';
      }
    }

    if (elBtn && elPhoneId && elToken) {
      elBtn.addEventListener('click', function () {
        const phoneId = (elPhoneId.value || '').trim();
        const token = (elToken.value || '').trim();

        if (!phoneId || !token) {
          window.toaster.toastError("Veuillez saisir l'ID de numéro de téléphone et le token d'accès.");
          return;
        }

        elBtn.setAttribute('disabled', 'disabled');
        setWhatsAppConfigurationApiCall(token, phoneId, (result) => {
          window.toaster.toastSuccess("Configuration enregistrée. Configurez maintenant le webhook côté Meta.");
          updateWebhookOutputs(result.webhook, result.verify_token);
        }, () => elBtn.removeAttribute('disabled'));
      });

      // Initial load
      getWhatsAppConfigurationApiCall(result => {
        if (result.webhook) {
          updateWebhookOutputs(result.webhook, result.verify_token);
        }
      });
    }
  })();
</script>
@endpush
