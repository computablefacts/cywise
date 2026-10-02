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

  const MORPH_MS = 220;
  const MORPH_EASING = 'cubic-bezier(0.2, 0.8, 0.2, 1)';

  // Apply a layout change (e.g. row => two-column panel) then animate the element
  // from its old height to the new one. Only [data-morph-fade] parts that just
  // appeared fade in: fading the whole element makes it blink.
  const morphHeight = async (el, change) => {

    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
      change();
      return;
    }

    // Measured before cancelling: a running morph keeps its current height
    const from = el.offsetHeight;
    el.getAnimations({subtree: true}).forEach(a => a.cancel());

    const fades = Array.from(el.querySelectorAll('[data-morph-fade]'));
    const hidden = fades.filter(f => f.offsetParent === null);

    // x-show reveals on the next animation frame: measure in that same frame, before it is painted
    change();
    await Alpine.nextTick();
    await new Promise(resolve => document.visibilityState === 'visible' ? requestAnimationFrame(resolve) : setTimeout(resolve));

    const to = el.offsetHeight;
    el.style.overflow = 'hidden';

    el.animate([{height: `${from}px`}, {height: `${to}px`}], {duration: MORPH_MS, easing: MORPH_EASING})
      .finished.then(() => el.style.overflow = '').catch(() => null);

    hidden.filter(f => f.offsetParent !== null)
      .forEach(f => f.animate([{opacity: 0}, {opacity: 1}], {duration: MORPH_MS, easing: 'ease-out'}));
  }

  /* SEARCH (x-ui.search) */

  // Case and accent insensitive: "Été" => "ete"
  const normalizeSearch = (text) => text.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();

  // Dates typed the French way match the ISO dates of the rows:
  // "28/09/2026" => "2026-09-28", "28/09" => "09-28", "09/2026" => "2026-09"
  const toIsoDate = (term) => {

    const day = term.match(/^(\d{1,2})\/(\d{1,2})(?:\/(\d{4}))?$/);
    if (day) {
      const [, d, m, y] = day;
      const monthDay = `${m.padStart(2, '0')}-${d.padStart(2, '0')}`;
      return y ? `${y}-${monthDay}` : monthDay;
    }

    const month = term.match(/^(\d{1,2})\/(\d{4})$/);
    if (month) {
      return `${month[2]}-${month[1].padStart(2, '0')}`;
    }
    return term;
  };

  // Keep the rows of the container containing every word of the query, e.g. "web-servers high".
  // Returns the counts shown by the search bar: {shown: 3, total: 42}.
  const searchRows = (container, query) => {

    if (!container) {
      return {shown: 0, total: 0};
    }

    const terms = normalizeSearch(query).split(/\s+/).filter(Boolean).map(toIsoDate);
    const matches = (text) => terms.every(term => normalizeSearch(text).includes(term));
    const show = (el, visible) => el.style.display = visible ? '' : 'none';
    const isShown = (el) => el.style.display !== 'none';

    const rows = Array.from(container.querySelectorAll('[data-search]'));
    rows.forEach(row => show(row, matches(`${row.dataset.search} ${row.textContent}`)));

    // Group (a domain and its subdomains): matching its name keeps all its rows,
    // matching one of its rows keeps the group and opens it
    container.querySelectorAll('[data-search-group]').forEach(group => {

      const rowsOfGroup = Array.from(group.querySelectorAll('[data-search]'));
      const isNameMatch = terms.length > 0 && matches(group.dataset.searchGroup);

      if (isNameMatch) {
        rowsOfGroup.forEach(row => show(row, true));
      }

      const isRowMatch = rowsOfGroup.some(isShown);
      show(group, isNameMatch || isRowMatch);

      if (terms.length > 0 && isRowMatch && !isNameMatch) {
        Alpine.$data(group).expanded = true;
      }
    });

    // Live counters, e.g. the inventory tab badges: <span data-search-count="#inventory-assets">
    container.querySelectorAll('[data-search-count]').forEach(el => {
      const list = container.querySelector(el.dataset.searchCount);
      el.textContent = Array.from(list.querySelectorAll('[data-search]')).filter(isShown).length;
    });

    // Empty message per list (e.g. one per inventory tab)
    container.querySelectorAll('[data-search-empty]').forEach(el => {
      const rowsOfList = Array.from(el.parentElement.querySelectorAll('[data-search]'));
      show(el, !rowsOfList.some(isShown));
    });

    const shown = rows.filter(isShown).length;

    return {shown: shown, total: rows.length};
  };

  const todaySeparatorHtmlTemplate = '{!! $today_separator !!}';

  const today = (() => {
    const date = new Date();
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
  })();

  /* SCROLL TO TOP */

  // Absent from most pages (only on conversations and notes): guard so the rest of the script still runs.
  const elScrollBtn = document.getElementById("scrollToTopBtn");

  if (elScrollBtn) {
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
  }

  /* NOTES */

  const elInputField = document.querySelector('.new-comment input');
  elInputField?.addEventListener('keydown', (event) => {
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

  // e.g. link "assets#aid-42" on a subdomain: open its group, then scroll to the row (or to the group for a root asset)
  const openGroupOf = (el) => {
    const group = el.closest('[data-search-group]');
    if (group) {
      Alpine.$data(group).expanded = true;
    }
    Alpine.nextTick(() => (el.offsetParent ? el : group ?? el).scrollIntoView({block: 'center'}));
  };

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

      // Same markup as the x-ui.tag chip rendered in iframes/timeline/_asset
      const wrapper = createTagChip(tag.tag);
      wrapper.id = `tag-${tag.id}`;

      const btn = document.createElement('button');
      btn.type = 'button';
      btn.title = "{{ __('Remove tag') }}";
      btn.className = 'ui:flex ui:size-4 ui:items-center ui:justify-center ui:rounded ui:border-0 ui:bg-transparent ui:p-0 ui:text-slate-400 ui:cursor-pointer ui:hover:bg-slate-200 ui:hover:text-ink';
      btn.innerHTML = '&times;';

      btn.onclick = () => removeTagFromAsset(String(assetId), String(tag.id));

      wrapper.appendChild(btn);
      list.appendChild(wrapper);

      window.toaster.toastSuccess("{{ __('Tag added.') }}");

      toggleTagInput(assetId);
      renderRowTags(assetId);
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
      renderRowTags(assetId);
    });
  }

  // Keep in sync with x-ui.tag tone="auto": same palette, same crc32 pick (e.g. "nginx" is always violet)
  const TAG_HUES = [
    'ui:bg-blue-50 ui:text-blue-700',
    'ui:bg-violet-50 ui:text-violet-700',
    'ui:bg-emerald-50 ui:text-emerald-700',
    'ui:bg-amber-50 ui:text-amber-800',
    'ui:bg-rose-50 ui:text-rose-700',
    'ui:bg-cyan-50 ui:text-cyan-800',
    'ui:bg-indigo-50 ui:text-indigo-700',
    'ui:bg-lime-50 ui:text-lime-800',
  ];

  // Keep in sync with $maxRowTags and $maxTags (iframes/timeline/_asset)
  const MAX_ROW_TAGS = 2;
  const MAX_ASSET_TAGS = 5;

  // Unsigned CRC-32 of the UTF-8 bytes, as PHP crc32()
  const crc32 = (text) => {
    let crc = 0xFFFFFFFF;
    for (const byte of new TextEncoder().encode(text)) {
      crc ^= byte;
      for (let i = 0; i < 8; i++) {
        crc = (crc >>> 1) ^ (0xEDB88320 & -(crc & 1));
      }
    }
    return (crc ^ 0xFFFFFFFF) >>> 0;
  };

  const createTagChip = (text) => {
    const chip = document.createElement('span');
    chip.className = `ui:inline-flex ui:items-center ui:gap-1 ui:rounded-md ui:px-2 ui:py-0.5 ui:text-xs ui:font-medium ${TAG_HUES[crc32(text.trim()) % TAG_HUES.length]}`;

    const label = document.createElement('span');
    label.textContent = text;
    chip.appendChild(label);

    return chip;
  };

  // Rebuild the row preview from the panel list, e.g. [prod] [web] +2
  const renderRowTags = (assetId) => {
    const elRow = document.getElementById(`row-tags-${assetId}`);
    const elTags = document.getElementById(`tags-${assetId}`);
    if (!elRow || !elTags) {
      return;
    }

    const labels = Array.from(elTags.children).map(el => el.firstElementChild.textContent.trim());

    // All tags stay searchable (x-ui.search), not only the previewed ones
    document.getElementById(`aid-${assetId}`).dataset.search = labels.join(' ');
    elRow.replaceChildren(...labels.slice(0, MAX_ROW_TAGS).map(createTagChip));

    if (labels.length <= MAX_ROW_TAGS) {
      return;
    }

    const more = document.createElement('span');
    more.className = 'ui:text-xs ui:font-medium ui:text-slate-500';
    more.textContent = `+${labels.length - MAX_ROW_TAGS}`;
    elRow.appendChild(more);
  };

  const toggleTagInput = (assetId) => {
    const elTags = document.getElementById(`tags-${assetId}`);
    const elAddTag = document.getElementById(`add-tag-${assetId}`);
    if (elTags && elAddTag && elTags.childElementCount <= MAX_ASSET_TAGS) {
      elAddTag.classList.remove('d-none');
    } else {
      elAddTag.classList.add('d-none');
    }
  };

  /* RULES DYNAMIC DISPLAY */

  const rulesDetails = @json($rules_details);

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
    elCard.style.display = '';

    // Title
    const elTitle = elCard.querySelector('#rule-title');
    if (data.can_edit) {
      const elLink = document.createElement('a');
      elLink.href = data.editor_url;
      elLink.className = 'ui:text-ink! ui:hover:text-brand-600!';
      elLink.textContent = data.display_name;
      elTitle.replaceChildren(elLink);
    } else {
      elTitle.textContent = data.display_name;
    }

    // Tactics
    const elTactics = elCard.querySelector('#rule-tactics');
    elTactics.innerHTML = '';

    // Same markup as x-ui.tag / x-ui.badge in pages/events
    const tagClass = 'ui:inline-flex ui:items-center ui:gap-1 ui:rounded-md ui:bg-slate-100 ui:px-2 ui:py-0.5 ui:text-xs ui:font-medium ui:text-slate-700';
    const badgeClass = 'ui:inline-flex ui:items-center ui:rounded-md ui:px-2 ui:py-0.5 ui:text-xs ui:font-medium ui:ring-1 ui:ring-inset ui:whitespace-nowrap';
    const badgeTone = {
      high: 'ui:bg-high-soft ui:text-red-600 ui:ring-red-200',
      medium: 'ui:bg-medium-soft ui:text-amber-700 ui:ring-amber-200',
      low: 'ui:bg-low-soft ui:text-emerald-700 ui:ring-emerald-200',
      info: 'ui:bg-info-soft ui:text-blue-700 ui:ring-blue-200',
      neutral: 'ui:bg-slate-100 ui:text-slate-600 ui:ring-slate-200',
    };

    (data.tactics || []).forEach(tactic => {
      const span = document.createElement('span');
      span.className = tagClass;
      span.textContent = tactic;
      elTactics.appendChild(span);
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
      iocHtml += `<span class="${badgeClass} ${badgeTone.high}">{{ __('yes') }}</span>`;
    } else {
      iocHtml += `<span class="${badgeClass} ${badgeTone.low}">{{ __('no') }}</span>`;
    }

    let scoreTone = badgeTone.neutral;

    if (data.score >= 75) {
      scoreTone = badgeTone.high;
    } else if (data.score >= 50) {
      scoreTone = badgeTone.medium;
    } else if (data.score >= 25) {
      scoreTone = badgeTone.info;
    }

    iocHtml += `<span class="${badgeClass} ${scoreTone}">${data.score}&nbsp;/&nbsp;100</span>`;
    elIocInfo.innerHTML = iocHtml;

    // Mitre
    const elMitreRow = elCard.querySelector('#rule-mitre-row');
    const elMitreLinks = elCard.querySelector('#rule-mitre-links');

    if (data.mitre && data.mitre.length > 0) {
      elMitreRow.style.display = '';
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
