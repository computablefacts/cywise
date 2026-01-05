<style>
  .bp4-popover2-transition-container {
    z-index: 9999;
  }
</style>
<div class="modal fade" id="shareModal" tabindex="-1" aria-hidden="true" role="dialog">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          {{ __('Partager') }}
        </h5>
        <button type="button" class="btn-close" aria-label="Close" onclick="closeShareModal()"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="shareContextType" value="">
        <input type="hidden" id="shareContextId" value="">
        <div class="mb-3">
          <label for="shareTagsWidget" class="form-label">
            {{ __('Tags') }}
          </label>
          <div id="shareTagsWidget"></div>
        </div>
        <div class="mb-3">
          <label for="shareEmailWidget" class="form-label">
            {{ __('Email du destinataire') }}
          </label>
          <div id="shareEmailWidget"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeShareModal()">
          {{ __('Annuler') }}
        </button>
        <button type="button" class="btn btn-primary" onclick="submitShare()">
          {{ __('Partager') }}
        </button>
      </div>
    </div>
  </div>
  <script>

    const widgets = {elTags: null, elEmail: null};

    window.openShareModal = (type, id) => {

      console.log('[Share] Open modal for', type, id);
      const elModal = document.getElementById('shareModal');

      if (!elModal) {
        return;
      }

      document.getElementById('shareContextType').value = type || '';
      document.getElementById('shareContextId').value = id || '';

      // MinimalMultiSelect for tags
      if (!widgets.elTags) {
        const elContainer = document.getElementById('shareTagsWidget');
        if (elContainer) {
          widgets.elTags = new com.computablefacts.blueprintjs.MinimalMultiSelect(elContainer);
          widgets.elTags.fillContainer = true;
        }
      }

      // MinimalTextInput for email
      if (!widgets.elEmail) {
        const elContainer = document.getElementById('shareEmailWidget');
        if (elContainer) {
          widgets.elEmail = new com.computablefacts.blueprintjs.MinimalTextInput(elContainer);
          widgets.elEmail.icon = 'envelope';
          widgets.elEmail.placeholder = 'email@example.com';
        }
      }

      // Load all tags and fill the multiselect
      listAllTagsApiCall((result) => {
        widgets.elTags.items = result.tags || [];
        console.log(widgets.elTags.items);
      });

      // Show modal
      elModal.classList.add('show');
      elModal.style.display = 'block';
      document.body.classList.add('modal-open');

      // Add backdrop
      let backdrop = document.getElementById('shareModalBackdrop');

      if (!backdrop) {
        backdrop = document.createElement('div');
        backdrop.id = 'shareModalBackdrop';
        backdrop.className = 'modal-backdrop fade show';
        backdrop.onclick = () => closeShareModal();
        document.body.appendChild(backdrop);
      }
    }

    window.closeShareModal = () => {

      const elModal = document.getElementById('shareModal');

      if (!elModal) {
        return;
      }

      elModal.classList.remove('show');
      elModal.style.display = 'none';
      document.body.classList.remove('modal-open');

      const backdrop = document.getElementById('shareModalBackdrop');

      if (backdrop && backdrop.parentNode) {
        backdrop.parentNode.removeChild(backdrop);
      }
    }

    window.submitShare = () => {

      const email = widgets.elEmail.value;
      const tags = widgets.elTags.selectedItems;

      console.log('[Share]', email, tags);
      // Call API
      shareAssetApiCall(tags, email);

      closeShareModal();
    }
  </script>
</div>
