@push('scripts')
<script>

  /* HELPERS */

  const apiCall = (method, url, params = {}, body = null) => {

    let fullUrl = "{{ app_url() }}/api" + url;

    if (method.toUpperCase() === "GET" && Object.keys(params).length > 0) {
      const queryParams = new URLSearchParams(params).toString();
      fullUrl += "?" + queryParams;
    }

    const headers = {
      'Content-Type': 'application/json', 'Authorization': 'Bearer {{ Auth::user()->sentinelApiToken() }}',
    };

    const options = {
      method: method, headers: headers, body: body ? JSON.stringify(body) : null,
    };

    return fetch(fullUrl, options).catch(error => {
      window.toaster.toastError(error);
      console.error(error);
    });
  }

  const todaySeparatorHtmlTemplate = '{!! $today_separator !!}';

  const today = (() => {
    const date = new Date();
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
  })();

  /* SCROLL TO TOP */

  const elScrollBtn = document.getElementById("scrollToTopBtn");

  window.onscroll = () => {
    if (document.body.scrollTop > (56 + 20) || document.documentElement.scrollTop > (56 + 20)) {
      elScrollBtn.classList.add("show");
    } else {
      elScrollBtn.classList.remove("show");
    }
  };

  elScrollBtn.onclick = () => {
    document.body.scrollTop = 0;
    document.documentElement.scrollTop = 0;
  };

  /* NOTES */

  const elInputField = document.querySelector('.new-comment input');
  elInputField.addEventListener('keydown', (event) => {
    if (event.key === 'Enter') {

      event.preventDefault();

      if (elInputField.value.trim() !== '') {

        const scopes = Array.from(document.querySelectorAll('.note-scope:checked')).map(cb => cb.value);

        if (scopes.length === 0) {
          window.toaster.toastError("{{ __('Please select at least one scope.') }}");
          return;
        }

        createNoteApiCall(elInputField.value.trim(), scopes, (response) => {

          elInputField.value = null;
          const elNote = (new DOMParser()).parseFromString(response.html, 'text/html');
          let elTodaySeparator = document.querySelector(`#sid-${today}`);

          if (elTodaySeparator) {
            const elOl = elTodaySeparator.nextElementSibling;
            elOl.insertBefore(elNote.body.firstChild, elOl.firstElementChild);
          } else {

            elTodaySeparator = (new DOMParser()).parseFromString(todaySeparatorHtmlTemplate, 'text/html');
            const elTimelines = document.querySelectorAll(`.timeline`);

            if (elTimelines.length >= 1) {

              // Insert OL
              const elOl = document.createElement('ol')
              elOl.classList.add('timeline');
              elTimelines[0].parentNode.insertBefore(elOl, elTimelines[0].nextElementSibling);

              // Insert separator
              elTimelines[0].parentNode.insertBefore(elTodaySeparator.body.firstChild,
                elTimelines[0].nextElementSibling);

              // Fill OL with note
              elOl.appendChild(elNote.body.firstChild);
            }
          }
          return response;
        });
      }
    }
  });

  const deleteNote = (noteId) => {
    deleteNoteApiCall(noteId, (response) => {
      const elNote = document.querySelector(`#nid-${noteId}`);
      if (elNote) {
        elNote.remove();
      }
      return response;
    });
  }

  /** CONVERSATIONS */

  const deleteConversation = (conversationId) => {
    const response = confirm("{{ __('Are you sure you want to delete this conversation?') }}");
    if (response) {
      deleteConversationApiCall(conversationId, (response) => window.toaster.toastSuccess(response.msg));
    }
  }

  /* EVENTS */

  const dismissEvent = (eventId) => dismissEventApiCall(eventId, () => window.toaster.toastSuccess("{{ __('Hide events like this for this server in the timeline.') }}"));

  /* VULNERABILITIES */

  const hideByUid = (uid) => toggleVulnerabilityVisibilityApiCall(uid, null, null);
  const hideByType = (type) => toggleVulnerabilityVisibilityApiCall(null, type, null);
  const hideByTitle = (title) => toggleVulnerabilityVisibilityApiCall(null, null, title);
  const startMonitoringAsset = (assetId) => monitorAssetApiCall(assetId,
    () => {
      document.querySelectorAll(`#start-monitoring-${assetId}`).forEach(el => el.classList.add('d-none'));
      document.querySelectorAll(`#stop-monitoring-${assetId}`).forEach(el => el.classList.remove('d-none'));
      document.querySelectorAll(`#restart-scan-${assetId}`).forEach(el => el.classList.remove('d-none'));
      document.querySelectorAll(`#delete-asset-${assetId}`).forEach(el => el.classList.add('d-none'));
      window.toaster.toastSuccess("{{ __('The monitoring started.') }}");
    });
  const stopMonitoringAsset = (assetId) => unmonitorAssetApiCall(assetId,
    () => {
      document.querySelectorAll(`#start-monitoring-${assetId}`).forEach(el => el.classList.remove('d-none'));
      document.querySelectorAll(`#stop-monitoring-${assetId}`).forEach(el => el.classList.add('d-none'));
      document.querySelectorAll(`#restart-scan-${assetId}`).forEach(el => el.classList.add('d-none'));
      document.querySelectorAll(`#delete-asset-${assetId}`).forEach(el => el.classList.remove('d-none'));
      window.toaster.toastSuccess("{{ __('The monitoring stopped.') }}");
    });
  const deleteAsset = (assetId) => deleteAssetApiCall(assetId,
    () => window.toaster.toastSuccess("{{ __('The asset will be deleted soon.') }}"));
  const restartScan = (assetId) => restartAssetScanApiCall(assetId,
    () => window.toaster.toastSuccess("{{ __('The scan has been restarted.') }}"));
  const toggleAutoMonitorNewSubdomains = (assetId) => toggleAutoMonitorNewSubdomainsApiCall(assetId,
    (response) => window.toaster.toastSuccess(response.msg));

  /* ASSETS TAGGING */

  const addTagToAsset = (assetId) => {

    const input = document.getElementById(`tag-input-${assetId}`);
    if (!input) {
      return;
    }

    const value = (input.value || '').trim();
    if (value.length === 0) {
      return;
    }

    tagAssetApiCall(assetId, value, (response) => {

      input.value = '';

      if (!response || !response.tag) {
        return;
      }

      const tag = response.tag; // {id, tag}

      // If already present, do nothing
      if (document.getElementById(`tag-${tag.id}`)) {
        window.toaster.toastSuccess("{{ __('Tag already present.') }}");
        return;
      }

      const list = document.getElementById(`tags-${assetId}`);
      if (!list) {
        return;
      }

      const wrapper = document.createElement('span');
      wrapper.id = `tag-${tag.id}`;
      wrapper.className = 'lozenge new d-inline-flex align-items-center';

      const label = document.createElement('span');
      label.textContent = tag.tag;

      const btn = document.createElement('button');
      btn.type = 'button';
      btn.title = "{{ __('Remove tag') }}";
      btn.className = 'bp4-button bp4-minimal border-0 bg-transparent cursor-pointer';
      btn.style.minHeight = '15px';
      btn.style.maxWidth = '15px';

      btn.onclick = () => removeTagFromAsset(String(assetId), String(tag.id));

      const icon = document.createElement('span');
      icon.className = 'bp4-icon bp4-icon-cross';
      btn.appendChild(icon);

      wrapper.appendChild(label);
      wrapper.appendChild(btn);
      list.appendChild(wrapper);

      window.toaster.toastSuccess("{{ __('Tag added.') }}");

      toggleTagInput(assetId);
    });
  }

  const removeTagFromAsset = (assetId, tagId) => {
    untagAssetApiCall(assetId, tagId, (response) => {
      const elTag = document.getElementById(`tag-${tagId}`);
      if (elTag) {
        elTag.remove();
      }
      const msg = response && response.msg ? response.msg : "{{ __('Tag removed.') }}";
      window.toaster.toastSuccess(msg);
      toggleTagInput(assetId);
    });
  }

  const toggleTagInput = (assetId) => {
    const elTags = document.getElementById(`tags-${assetId}`);
    const elAddTag = document.getElementById(`add-tag-${assetId}`);
    if (elTags && elAddTag && elTags.childElementCount <= 5) {
      elAddTag.classList.remove('d-none');
    } else {
      elAddTag.classList.add('d-none');
    }
  };

  /* RULES DYNAMIC DISPLAY */

  const rulesDetails = @json($rulesDetails);

  const updateRuleDisplay = (ruleName) => {

    const elCard = document.getElementById('selected-rule-card');

    if (!elCard) {
      return;
    }
    if (!ruleName || !rulesDetails[ruleName]) {
      elCard.style.display = 'none';
      return;
    }

    const data = rulesDetails[ruleName];
    elCard.style.display = 'flex';

    // Title
    const elTitle = elCard.querySelector('#rule-title');
    if (data.can_edit) {
      elTitle.innerHTML = `<a href="${data.editor_url}">${data.display_name}</a>`;
    } else {
      elTitle.textContent = data.display_name;
    }

    // Tactics
    const elTactics = elCard.querySelector('#rule-tactics');
    elTactics.innerHTML = '';
    (data.tactics || []).forEach(tactic => {
      const span = document.createElement('span');
      span.className = 'lozenge new';
      span.textContent = tactic;
      elTactics.appendChild(span);
      elTactics.appendChild(document.createTextNode('\u00A0'));
    });

    // Description
    elCard.querySelector('#rule-description').textContent = data.description;

    // Platform
    elCard.querySelector('#rule-platform').textContent = data.platform;
    elCard.querySelector('#rule-interval').textContent = data.interval;

    // IoC & Score
    const elIocInfo = elCard.querySelector('#rule-ioc-info');
    let iocHtml = '';

    if (data.is_ioc) {
      iocHtml += `<span class="lozenge error">{{ __('yes') }}</span>&nbsp;`;
    } else {
      iocHtml += `<span class="lozenge success">{{ __('no') }}</span>&nbsp;`;
    }

    let scoreClass = 'neutral';

    if (data.score >= 75) {
      scoreClass = 'error';
    } else if (data.score >= 50) {
      scoreClass = 'warning';
    } else if (data.score >= 25) {
      scoreClass = 'information';
    }

    iocHtml += `<span class="lozenge ${scoreClass}">${data.score}&nbsp;/&nbsp;100</span>`;
    elIocInfo.innerHTML = iocHtml;

    // Mitre
    const elMitreRow = elCard.querySelector('#rule-mitre-row');
    const elMitreLinks = elCard.querySelector('#rule-mitre-links');

    if (data.mitre && data.mitre.length > 0) {
      elMitreRow.style.display = 'flex';
      elMitreLinks.innerHTML = '';
      data.mitre.forEach(m => {
        const a = document.createElement('a');
        a.href = m.url;
        a.target = '_blank';
        a.textContent = m.uid;
        elMitreLinks.appendChild(a);
        elMitreLinks.appendChild(document.createTextNode('\u00A0'));
      });
    } else {
      elMitreRow.style.display = 'none';
    }

    // Query
    elCard.querySelector('#rule-query').textContent = data.query;
  };

  document.querySelectorAll('select[name="rule_name"]').forEach(select => {
    select.addEventListener('change', (e) => {
      updateRuleDisplay(e.target.value);
    });
  });

</script>
@endpush
