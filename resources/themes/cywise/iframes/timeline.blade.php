@extends('theme::iframes.app')

@push('styles')
<style>

  .timeline {
    width: 85%;
    max-width: 100%;
    margin-left: 80px;
    margin-right: auto;
    display: flex;
    flex-direction: column;
    padding: 32px 0 32px 32px;
    border-left: 2px solid var(--c-grey-200);
    font-size: 1rem;
    margin-bottom: 0;
  }

  .timeline-item {
    display: flex;
    gap: 24px;
  }

  .timeline-item + * {
    margin-top: 24px;
  }

  .timeline-item + .extra-space {
    margin-top: 48px;
  }

  .new-comment {
    width: 100%;
  }

  .new-comment input {
    border: 1px solid var(--c-grey-200);
    border-radius: 6px;
    height: 48px;
    padding: 0 16px;
    width: 100%;
  }

  .new-comment input::-moz-placeholder {
    color: var(--c-grey-300);
  }

  .new-comment input:-ms-input-placeholder {
    color: var(--c-grey-300);
  }

  .new-comment input::placeholder {
    color: var(--c-grey-300);
  }

  .new-comment input:focus {
    border-color: var(--c-grey-300);
    outline: 0;
    box-shadow: 0 0 0 4px var(--c-grey-100);
  }

  .timeline-item-hour {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    margin-left: -65px;
    flex-shrink: 0;
    color: var(--c-grey-400);
  }

  .timeline-item-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    margin-left: -52px;
    flex-shrink: 0;
    overflow: hidden;
    box-shadow: 0 0 0 6px #fff;
  }

  .timeline-item-icon svg {
    width: 20px;
    height: 20px;
  }

  .timeline-item-icon.faded-icon {
    background-color: var(--c-grey-100);
    color: var(--c-grey-400);
  }

  .timeline-item-icon.filled-icon {
    background-color: var(--c-blue);
    color: #fff;
  }

  .timeline-item-wrapper {
    width: 100%;
  }

  .timeline-item-description {
    display: flex;
    gap: 8px;
    color: var(--c-grey-400);
    align-items: center;
  }

  .timeline-item-description img {
    flex-shrink: 0;
  }

  .timeline-item-description b {
    color: var(--c-grey-500);
    font-weight: 500;
    text-decoration: none;
  }

  .avatar {
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    overflow: hidden;
    aspect-ratio: 1/1;
    flex-shrink: 0;
    width: 40px;
    height: 40px;
  }

  .avatar.small {
    width: 28px;
    height: 28px;
  }

  .avatar img {
    -o-object-fit: cover;
    object-fit: cover;
  }

  .comment {
    margin-top: 12px;
    color: var(--c-grey-500);
    border: 1px solid var(--c-grey-200);
    box-shadow: 0 4px 4px 0 var(--c-grey-100);
    border-radius: 6px;
    padding: 16px;
    font-size: 0.8rem;
  }

  .button {
    border: 0;
    display: inline-flex;
    vertical-align: middle;
    margin-right: 4px;
    margin-top: 12px;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    height: 32px;
    padding: 0 8px;
    background-color: var(--c-grey-100);
    flex-shrink: 0;
    cursor: pointer;
    border-radius: 99em;
  }

  .button:hover {
    background-color: var(--c-grey-200);
  }

  .button.square {
    border-radius: 50%;
    color: var(--c-grey-400);
    f
    width: 32px;
    height: 32px;
    padding: 0;
  }

  .button.square svg {
    width: 24px;
    height: 24px;
  }

  .button.square:hover {
    background-color: var(--c-grey-200);
    color: var(--c-grey-500);
  }

  .show-replies {
    color: var(--c-grey-300);
    background-color: transparent;
    border: 0;
    padding: 0;
    margin-top: 16px;
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 1rem;
    cursor: pointer;
  }

  .show-replies svg {
    flex-shrink: 0;
    width: 24px;
    height: 24px;
  }

  .show-replies:hover, .show-replies:focus {
    color: var(--c-grey-500);
  }

  .avatar-list {
    display: flex;
    align-items: center;
  }

  .avatar-list > * {
    position: relative;
    box-shadow: 0 0 0 2px #fff;
    margin-right: -8px;
  }

  /* TABLE */

  .timeline-item-wrapper table {
    border-collapse: collapse;
    caption-side: bottom;
    display: table;
    width: 100%;
    font-size: 0.8rem;
    margin-top: 0;
  }

  .timeline-item-wrapper table thead {
    border-top-width: 1px;
    display: table-header-group;
    font-weight: 500;
    border-color: rgb(226, 232, 240);
    border-style: solid;
  }

  .timeline-item-wrapper table tr {
    border-bottom-width: 1px;
    display: table-row;
    border-color: rgb(226, 232, 240);
    border-style: solid;
  }

  .timeline-item-wrapper table tbody {
    display: table-row-group
  }

  .timeline-item-wrapper table thead tr th {
    padding: 0.5rem;
    vertical-align: middle;
    display: table-cell;
    height: 2rem;
  }

  .timeline-item-wrapper table tbody tr td {
    padding: 0.5rem;
    vertical-align: middle;
    display: table-cell;
  }

  /* SCROLL TO TOP */

  .scroll-to-top {
    position: fixed;
    top: calc(56px + 20px);
    right: 20px;
    background-color: var(--c-blue);
    color: white;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    border: none;
    cursor: pointer;
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    transition: all 0.3s ease;
  }

  .scroll-to-top:hover {
    background-color: var(--c-grey-500);
    transform: translateY(-1px);
  }

  .scroll-to-top.show {
    display: flex;
  }

</style>
@endpush

@section('content')
@if(request()->routeIs('iframes.assets'))
@include('theme::iframes.timeline._asset-counters')
@endif
@if(request()->routeIs('iframes.conversations'))
@include('theme::iframes.timeline._conversation-counters')
@endif
@if(request()->routeIs('iframes.ioc'))
@include('theme::iframes.timeline._ioc-counters')
@endif
@if(request()->routeIs('iframes.notes-and-memos'))
@include('theme::iframes.timeline._note-counters')
@endif
@if(request()->routeIs('iframes.vulnerabilities'))
@include('theme::iframes.timeline._vulnerability-counters')
@endif
<div class="row mt-3 mb-3">
  <div class="col">
    <div class="card">
      <div class="card-body">
        <ol class="timeline">
          <li class="timeline-item">
            <span class="timeline-item-icon | filled-icon">
              <span class="bp4-icon bp4-icon-manually-entered-data"></span>
            </span>
            <div class="new-comment">
              <input type="text" placeholder="{{ __('Add a note... (press Enter to submit)') }}"/>
            </div>
          </li>
        </ol>
        @foreach($items as $date => $times)
        @if(empty($dateId) || $date === $dateId)
        @include('theme::iframes.timeline._separator', ['date' => $date])
        <ol class="timeline">
          @foreach($times as $time => $events)
          @foreach($events as $event)
          {!! $event['html'] !!}
          @endforeach
          @endforeach
        </ol>
        @endif
        @endforeach
      </div>
    </div>
  </div>
  <button id="scrollToTopBtn" class="scroll-to-top" title="Go to top">
    <span class="bp4-icon bp4-icon-arrow-up"></span>
  </button>
</div>
@endsection

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
      toaster.toastError(error);
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

        createNoteApiCall(elInputField.value.trim(), (response) => {

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
      axios.delete(`/conversations/${conversationId}`).then(function (response) {
        if (response.data.success) {
          toaster.toastSuccess(response.data.success);
        } else if (response.data.error) {
          toaster.toastError(response.data.error);
        } else {
          console.log(response.data);
        }
      }).catch(error => toaster.toastAxiosError(error));
    }
  }

  /* EVENTS */

  const dismissEvent = (eventId) => {
    dismissEventApiCall(eventId);
  }

  /* VULNERABILITIES */

  const hideByUid = (uid) => {
    toggleVulnerabilityVisibilityApiCall(uid, null, null);
  }

  const hideByType = (type) => {
    toggleVulnerabilityVisibilityApiCall(null, type, null);
  }

  const hideByTitle = (title) => {
    toggleVulnerabilityVisibilityApiCall(null, null, title);
  }

  const startMonitoringAsset = (assetId) => {
    apiCall('POST', `/inventory/asset/${assetId}/monitoring/begin`)
    .then((response) => {
      if (response.ok) {
        toaster.toastSuccess("{{ __('The monitoring started.') }}");
      } else {
        toaster.toastError("{{ __('An error occurred.') }}")
      }
    });
  }

  const stopMonitoringAsset = (assetId) => {
    apiCall('POST', `/inventory/asset/${assetId}/monitoring/end`)
    .then((response) => {
      if (response.ok) {
        toaster.toastSuccess("{{ __('The monitoring stopped.') }}");
      } else {
        toaster.toastError("{{ __('An error occurred.') }}")
      }
    });
  }

  const deleteAsset = (assetId) => {
    apiCall('DELETE', `/adversary/assets/${assetId}`)
    .then((response) => {
      if (response.ok) {
        toaster.toastSuccess("{{ __('The asset will be deleted soon.') }}");
      } else {
        toaster.toastError("{{ __('An error occurred.') }}")
      }
    });
  }

  const restartScan = (assetId) => {
    apiCall('POST', `/adversary/assets/restart/${assetId}`)
    .then((response) => {
      if (response.ok) {
        toaster.toastSuccess("{{ __('The scan has been restarted.') }}");
      } else {
        toaster.toastError("{{ __('An error occurred.') }}")
      }
    });
  }

</script>
@endpush

