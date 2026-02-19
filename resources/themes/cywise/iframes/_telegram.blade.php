@include('theme::iframes._json-rpc')

<div class="card mt-3">
  <div class="card-body">
    <h6 class="card-title text-truncate">
      Interagir avec Cywise au moyen d'un bot Telegram
    </h6>
    <p class="mb-2">
      Pour utiliser Cywise depuis Telegram, vous devez créer un bot Telegram et configurer son webhook.
    </p>
    <p>
      <b>1. Créer un bot</b>
    </p>
    <p>
      Sur Telegram, ouvrez la conversation avec <code>@BotFather</code>, puis envoyez la commande <code>/newbot</code>.
    </p>
    <p>
      Suivez les instructions (nom, identifiant se terminant par "bot"). À la fin, BotFather vous donne un "token
      d'API".
    </p>
    <p>
      <b>2. Enregistrer le token auprès de Cywise</b>
    </p>
    <p>
      Collez ci-dessous le token retourné par <code>@BotFather</code>, puis cliquez sur "Enregistrer".
    </p>
    <div class="row g-2 align-items-center mb-2">
      <div class="col-9 col-md-6">
        <input id="tg-bot-token" type="text" class="form-control" placeholder="1234567890:ABCDEF..."
               value="{{ Auth::user()->telegram_bot_token ?? '' }}">
      </div>
      <div class="col-auto">
        <button id="tg-save-token" class="btn btn-primary">
          <span class="bp4-icon bp4-icon-tick"></span>
          Enregistrer
        </button>
      </div>
    </div>
    <p>
      <b>3. Configurer le webhook côté Telegram</b>
    </p>
    <p>
      Après avoir enregistré le token auprès de Cywise, vous obtiendrez l'URL d'un webhook. Utilisez la commande curl
      ci-dessous pour déclarer ce webhook auprès de Telegram :
    </p>
    <pre class="mb-2" style="white-space: pre-wrap"><code id="tg-webhook-curl">(en attente — enregistrez d'abord le token)</code></pre>
    <p>
      <b>Astuce :</b> vous pouvez ensuite vérifier la configuration de Telegram avec <code>getWebhookInfo</code> :
    </p>
    <pre class="mb-2" style="white-space: pre-wrap"><code id="tg-webhook-info">curl -s https://api.telegram.org/bot&lt;BOT_TOKEN&gt;/getWebhookInfo | jq</code></pre>
    <p>
      <b>4. C'est fini ! Vous pouvez maintenant interagir avec Cywise au moyen de Telegram</b>
    </p>
    <p>
      Une fois le webhook actif, envoyez un message à votre bot Telegram : il répondra via <a
          href="{{ route('iframes.cyberbuddy') }}">CyberBuddy</a>, dans le contexte de votre compte Cywise.
    </p>
  </div>
</div>

@push('scripts')
<script>
  (function () {

    const elBtn = document.getElementById('tg-save-token');
    const elInput = document.getElementById('tg-bot-token');
    const elWebhookCurl = document.getElementById('tg-webhook-curl');
    const elWebhookInfo = document.getElementById('tg-webhook-info');

    function updateWebhookOutputs(webhook, token) {
      if (token && webhook && elWebhookCurl) {
        elWebhookCurl.textContent = `curl -s "https://api.telegram.org/bot${token}/setWebhook" -d url=${webhook}`;
      }
      if (token && elWebhookInfo) {
        elWebhookInfo.textContent = `curl -s https://api.telegram.org/bot${token}/getWebhookInfo | jq`;
      }
    }

    if (elBtn && elInput) {
      elBtn.addEventListener('click', function () {
        const token = (elInput.value || '').trim();
        if (!token) {
          if (toaster) {
            toaster.toastError("Veuillez saisir un token de bot Telegram.");
          }
          return;
        }
        elBtn.setAttribute('disabled', 'disabled');
        setTelegramConfigurationApiCall(token, (result) => {
          if (toaster) {
            toaster.toastSuccess("Token enregistré. Configurez maintenant le webhook côté Telegram.");
          }
          updateWebhookOutputs(result.webhook, token);
        }, () => elBtn.removeAttribute('disabled'));
      });

      const existingToken = (elInput.value || '').trim();

      if (existingToken) {
        elWebhookCurl.textContent = "Cliquez sur \"Enregistrer\" pour afficher la commande curl du webhook.";
      }

      getTelegramConfigurationApiCall(result => updateWebhookOutputs(result['webhook'], result['bot_token']));
    }
  })();
</script>
@endpush