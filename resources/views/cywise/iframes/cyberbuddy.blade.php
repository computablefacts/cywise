@extends('cywise.iframes.app')

@php

$conversationId = request()->query('conversation_id');
$userId = Auth::user()?->id;

if ($conversationId) {
$conversation = \App\Models\Conversation::where('id', $conversationId)
->where('format', \App\Models\Conversation::FORMAT_V1)
->where('created_by', $userId)
->first();
}

$conversation = $conversation ?? \App\Models\Conversation::create([
'thread_id' => \Illuminate\Support\Str::random(10),
'dom' => json_encode([]),
'autosaved' => true,
'created_by' => $userId,
'format' => \App\Models\Conversation::FORMAT_V1,
]);

@endphp

@push('styles')
<style>

  .tw-wrapper1 {
    color: rgb(2, 8, 23);
    flex-direction: column;
    flex-grow: 1;
    display: flex;
    overflow: hidden;
    height: 100vh;
  }

  .tw-wrapper2 {
    flex-grow: 1;
    overflow-x: hidden;
    overflow-y: auto
  }

  .tw-chat {
    background-color: rgb(255, 255, 255);
    flex-direction: column;
    display: flex;
    width: 100%;
    height: 100%;
    border-width: 2px;
    border-color: rgb(226, 232, 240);
    border-style: solid;
    border-radius: 8px
  }

  .tw-chat-header {
    flex-direction: column;
    padding: 1rem;
    display: flex;
    font-size: 24px;
    font-weight: 600
  }

  .tw-chat-wrapper {
    align-items: center;
    padding: 1rem;
    display: flex;
    width: 80%;
    margin: auto;
  }

  .tw-chat-footer {
    flex-direction: column;
    display: flex;
    width: 100%
  }

  .tw-chat-footer-input {
    cursor: text;
    flex-grow: 1;
    font-size: 14px;
    padding-bottom: 0.5rem;
    padding-left: 0.75rem;
    padding-right: 0.75rem;
    padding-top: 0.5rem;
    display: flex;
    width: 100%;
    height: 2.5rem;
    border-width: 2px;
    border-color: rgb(226, 232, 240);
    border-style: solid;
    border-radius: 6px
  }

  .tw-chat-footer-input:focus {
    outline: 2px solid black;
    outline-offset: 2px;
  }

  .tw-chat-footer-wrapper {
    display: flex;
    margin-top: .5rem;
    margin-bottom: 0
  }

  .tw-chat-footer-upload {
    color: black;
    align-items: center;
    cursor: pointer;
    justify-content: center;
    display: inline-flex;
    width: 2.5rem;
    height: 2.5rem;
    border-width: 0;
    border-radius: 6px;
  }

  .tw-chat-footer-upload:hover {
    color: rgb(250, 250, 250);
    background-color: var(--c-blue);
  }

  .tw-chat-footer-upload-svg {
    width: 1rem;
    height: 1rem
  }

  .tw-chat-footer-upload-svg-rect {
    width: 18px;
    height: 18px
  }

  .tw-chat-footer-send {
    color: rgb(250, 250, 250);
    background-color: var(--c-blue);
    align-items: center;
    font-size: 14px;
    font-weight: 500;
    justify-content: center;
    opacity: 0.5;
    padding-bottom: 0.5rem;
    padding-left: 1rem;
    padding-right: 1rem;
    padding-top: 0.5rem;
    text-align: center;
    display: inline-flex;
    width: 100%;
    height: 2.5rem;
    margin-left: .5rem;
    border-radius: 6px;
    border: unset;
  }

  .tw-chat-footer-send.bactive {
    opacity: 1;
  }

  .tw-chat-footer-send.bactive:hover {
    background-color: hsl(238 83% 60% / .9);
  }

  .tw-chat-footer-send-svg {
    width: 1rem;
    height: 1rem;
    margin-right: 0.5rem
  }

  .tw-chat-header-title {
    margin-bottom: 0;
    color: var(--c-orange-light);
  }

  .tw-disabled {
    opacity: 0.5 !important;
    pointer-events: none;
  }

  .tw-conversation-wrapper {
    flex-grow: 1;
    overflow: hidden
  }

  .tw-conversation {
    padding: 1rem;
    height: 100%;
    overflow: hidden;
    overflow-y: auto;
  }

  .typing-wrapper {
    height: 25px;
    display: flex;
    align-items: center;
  }

  .typing {
    position: relative;
    margin-left: .5rem;
  }

  .typing span {
    content: "";
    -webkit-animation: blink 1.5s infinite;
    animation: blink 1.5s infinite;
    -webkit-animation-fill-mode: both;
    animation-fill-mode: both;
    height: 10px;
    width: 10px;
    background: hsl(238 83% 60% / .9);
    position: absolute;
    left: 0;
    top: 0;
    border-radius: 50%;
  }

  .typing span:nth-child(2) {
    -webkit-animation-delay: 0.2s;
    animation-delay: 0.2s;
    margin-left: 15px;
  }

  .typing span:nth-child(3) {
    -webkit-animation-delay: 0.4s;
    animation-delay: 0.4s;
    margin-left: 30px;
  }

  @-webkit-keyframes blink {
    0% {
      opacity: 0.1;
    }
    20% {
      opacity: 1;
    }
    100% {
      opacity: 0.1;
    }
  }

  @keyframes blink {
    0% {
      opacity: 0.1;
    }
    20% {
      opacity: 1;
    }
    100% {
      opacity: 0.1;
    }
  }

  @media (min-width: 640px) {

    .tw-chat-footer {
      flex-direction: row !important;
    }

    .tw-chat-footer-wrapper {
      margin-right: 0 !important;
      margin-left: .5rem !important;
      margin-top: 0px !important;
      margin-bottom: 0 !important;
    }

    .tw-chat-footer-send {
      width: auto !important;
    }
  }

  .tw-question-wrapper {
    flex-direction: column;
    display: flex;
    margin-bottom: 1rem
  }

  .tw-question {
    align-items: flex-start;
    flex-direction: row-reverse;
    display: flex
  }

  .tw-question-avatar-wrapper {
    margin-left: 0.5rem
  }

  .tw-question-avatar {
    background-color: rgb(224, 231, 255);
    border-radius: 10000px;
    padding: 0.5rem
  }

  .tw-question-avatar span {
    display: flex;
    width: 1.5rem;
    height: 1.5rem;
    border-radius: 10000px;
    overflow: hidden
  }

  .tw-question-avatar img {
    aspect-ratio: 1 / 1;
    width: 100%;
    height: 100%;
    max-width: 100%
  }

  .tw-question-directive {
    color: rgb(0, 0, 0);
    background-color: rgb(224, 231, 255);
    display: inline-block;
    /* max-width: 85%; */
    margin-left: .5rem;
    border-radius: 8px;
    padding: 0.75rem
  }

  .tw-question-timestamp {
    color: rgb(107, 114, 128);
    font-size: 12px;
    padding-right: 48px;
    padding-top: 0.5rem;
    text-align: right;
  }

  .tw-answer-wrapper {
    flex-direction: column;
    display: flex;
    margin-bottom: 1rem
  }

  .tw-answer {
    align-items: flex-start;
    display: flex
  }

  .tw-avatar-color {
    color: var(--c-blue);
  }

  .tw-answer-avatar-wrapper {
    margin-right: 0.5rem;
    color: var(--c-blue);
  }

  .tw-answer-avatar {
    background-color: rgba(68, 74, 238, 0.1);
    border-radius: 10000px;
    padding: 0.5rem;
  }

  .tw-answer-message {
    color: rgb(0, 0, 0);
    background-color: rgba(68, 74, 238, 0.1);
    display: inline-block;
    max-width: 85%;
    margin-left: .5rem;
    border-radius: 8px;
    padding: 0.75rem;
  }

  .tw-answer-message-paragraph {
    margin-bottom: 0.5rem
  }

  .tw-answer-message-html {
    /* background-color: rgb(255, 255, 255); */
    /* margin-top: 1rem; */
    border-radius: 8px;
    /* padding: 1rem */
    --font-size: 16px;
  }

  .tw-answer-message-html h1 {
    font-size: calc(var(--font-size) + 4px);
  }

  .tw-answer-message-html h2 {
    font-size: calc(var(--font-size) + 2px);
  }

  .tw-answer-message-html h3 {
    font-size: calc(var(--font-size));
  }

  .tw-answer-message-html p {
    margin-bottom: 0;
  }

  .tw-answer-timestamp {
    color: rgb(107, 114, 128);
    font-size: 12px;
    padding-left: calc(48px + 0.75rem);
    padding-top: 0.5rem;
  }

  /* COMMANDS */

  .tw-answer-command {
    background-color: rgb(255, 255, 255);
    align-items: center;
    cursor: pointer;
    justify-content: center;
    padding-left: 0.75rem;
    padding-right: 0.75rem;
    text-align: center;
    display: inline-flex;
    width: 160.55px;
    height: 2.25rem;
    border-width: 1px;
    border-color: rgb(226, 232, 240);
    border-style: solid;
    border-radius: 6px
  }

  .tw-answer-command:hover {
    background-color: var(--c-blue);
    color: white;
  }

  /* DOCUMENT */

  .tw-answer-document {
    align-items: center;
    display: flex;
    margin-top: 0.5rem;
    font-size: 14px;
    font-weight: 500
  }

  .tw-answer-document-button {
    background-color: rgb(255, 255, 255);
    align-items: center;
    cursor: pointer;
    justify-content: center;
    padding-bottom: 0.5rem;
    padding-left: 1rem;
    padding-right: 1rem;
    padding-top: 0.5rem;
    text-align: center;
    display: flex;
    width: 232.633px;
    height: 2.5rem;
    border-width: 0;
    border-radius: 6px;
    gap: 8px
  }

  .tw-answer-document-button:hover {
    background-color: #444aee;
    color: white;
  }

  .tw-answer-document-icon-svg {
    width: 1rem;
    height: 1rem
  }

  .tw-answer-document-download-svg {
    width: 1rem;
    height: 1rem;
    margin-left: 0.5rem;
  }

  /* CHART */

  .tw-answer-chart {
    background-color: rgb(255, 255, 255);
    margin-top: 1rem;
    border-radius: 8px;
    padding: 1rem
  }

  /* IMAGE */

  .tw-answer-image {
    width: 802.5px;
    height: auto;
    max-width: 100%;
    margin-top: 0.5rem;
    margin-bottom: 0.5rem;
    border-radius: 8px
  }

  /* TABLE */

  .tw-answer-table-wrapper {
    background-color: rgb(255, 255, 255);
    width: 100%;
    margin-top: 1rem;
    border-radius: 8px;
    overflow: auto;
    padding: 1rem;
    font-size: 14px
  }

  .tw-answer-table {
    width: 100%;
    overflow: auto
  }

  .tw-answer-table table {
    border-collapse: collapse;
    caption-side: bottom;
    display: table;
    width: 100%
  }

  .tw-answer-table table thead {
    display: table-header-group;
    color: rgb(100, 116, 139);
    font-weight: 500
  }

  .tw-answer-table table tr {
    border-bottom-width: 2px;
    display: table-row;
    border-color: rgb(226, 232, 240);
    border-style: solid;
  }

  .tw-answer-table table tbody {
    display: table-row-group
  }

  .tw-answer-table table thead tr th {
    padding-bottom: 2px;
    padding-left: 1rem;
    padding-right: 1rem;
    padding-top: 2px;
    vertical-align: middle;
    display: table-cell;
    height: 2rem;
  }

  .tw-answer-table table thead tr th.left {
    text-align: left;
  }

  .tw-answer-table table thead tr th.right {
    text-align: right;
  }

  .tw-answer-table table tbody tr td {
    vertical-align: middle;
    display: table-cell;
    padding: 0.5rem;
  }

  .tw-answer-table table tbody tr td.left {
    text-align: left;
  }

  .tw-answer-table table tbody tr td.right {
    text-align: right;
  }

  /* STARS */

  .stars {
    --rating: 0;
    --percent: calc(var(--rating) / 1 * 100%);
    display: inline-block;
    font-size: 20px;
    font-family: Times;
    line-height: 1;
    text-align: right;
  }

  .stars::before {
    content: "★";
    letter-spacing: 3px;
    background: linear-gradient(90deg, var(--c-orange-light) var(--percent), #fff var(--percent));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
  }

  /* TOOLTIP */

  .cb-tooltip-list {
    position: relative;
    display: inline-block;
    border-bottom: 1px dotted black; /* If you want dots under the hoverable text */
    cursor: pointer;
  }

  .cb-tooltip {
    position: relative;
    display: inline-block;
    border-bottom: 1px dotted var(--c-orange-light); /* If you want dots under the hoverable text */
    cursor: pointer;
  }

  .cb-tooltip-list .cb-tooltiptext,
  .cb-tooltip .cb-tooltiptext {
    visibility: hidden;
    width: 650px;
    background-color: var(--c-orange-light);
    color: white;
    text-align: left;
    padding: 5px 5px;

    /* Position the tooltip text */
    position: absolute;
    z-index: 1;

    /* Fade in tooltip */
    opacity: 0;
    transition: opacity 0.3s;
  }

  .cb-tooltip-list:hover .cb-tooltiptext,
  .cb-tooltip:hover .cb-tooltiptext {
    visibility: visible;
    opacity: 1;
  }

  .cb-tooltip-list-top {
    bottom: 125%;
    left: 0;
  }

  .cb-tooltip-top {
    bottom: 125%;
    left: 50%;
    margin-left: -60px;
  }

  .cb-tooltip-list-top::after,
  .cb-tooltip-top::after {
    /* content: ""; */
    position: absolute;
    top: 100%;
    left: 50%;
    margin-left: -5px;
    border-width: 5px;
    border-style: solid;
    border-color: var(--c-orange-light) transparent transparent transparent;
  }

  .cb-tooltip-bottom {
    top: 135%;
    left: 50%;
    margin-left: -60px;
  }

  .cb-tooltip-bottom::after {
    /* content: ""; */
    position: absolute;
    bottom: 100%;
    left: 50%;
    margin-left: -5px;
    border-width: 5px;
    border-style: solid;
    border-color: transparent transparent var(--c-orange-light) transparent;
  }

  .cb-tooltip-left {
    top: -5px;
    bottom: auto;
    right: 128%;
  }

  .cb-tooltip-left::after {
    /* content: ""; */
    position: absolute;
    top: 50%;
    left: 100%;
    margin-top: -5px;
    border-width: 5px;
    border-style: solid;
    border-color: transparent transparent transparent var(--c-orange-light);
  }

  .cb-tooltip-right {
    top: -5px;
    left: 125%;
  }

  .cb-tooltip-right::after {
    /* content: ""; */
    position: absolute;
    top: 50%;
    right: 100%;
    margin-top: -5px;
    border-width: 5px;
    border-style: solid;
    border-color: transparent var(--c-orange-light) transparent transparent;
  }

</style>
@endpush

@section('content')
<div class="tw-wrapper1 pt-3 pb-3">
  <div class="tw-wrapper2">
    <div class="tw-chat">

      <!-- HEADER -->
      <div class="tw-chat-header">
        <h3 class="tw-chat-header-title">
          <!-- <img alt="Bear" fetchpriority="high" width="250" height="250" decoding="async" data-nimg="1"
               style="color:transparent;width:50px;height:50px" src="https://www.svgrepo.com/show/10913/bear.svg"> -->
          CyberBuddy
        </h3>
      </div>

      <!-- CONVERSATION -->
      <div class="tw-conversation-wrapper">
        <div class="tw-conversation">
          <!-- DYNAMICALLY FILLED -->
          @include('cywise.iframes.cyberbuddy._actions')
        </div>
      </div>

      <!-- INPUT FIELD -->
      <div class="tw-chat-wrapper">
        <div class="tw-chat-footer">
          <input value="" type="text" placeholder="{{ __('Ask me anything!') }}" class="tw-chat-footer-input"/>
          <div class="tw-chat-footer-wrapper">
            <button class="tw-chat-footer-upload">
              <svg xmlns="http://www.w3.org/2000/svg" height="24" viewbox="0 0 24 24" fill="none" stroke="currentColor"
                   stroke-linecap="round" stroke-linejoin="round" class="tw-chat-footer-upload-svg">
                <rect height="18" x="3" y="3" rx="2" ry="2" fill="none" stroke="currentColor"
                      class="tw-chat-footer-upload-svg-rect"></rect>
                <circle cx="9" cy="9" r="2" fill="none" stroke="currentColor"></circle>
                <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21" fill="none" stroke="currentColor"></path>
              </svg>
            </button>
            <button class="tw-chat-footer-send bactive">
              <svg xmlns="http://www.w3.org/2000/svg" height="24" viewbox="0 0 24 24" fill="none"
                   stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" class="tw-chat-footer-send-svg">
                <path
                  d="M14.536 21.686a.5.5 0 0 0 .937-.024l6.5-19a.496.496 0 0 0-.635-.635l-19 6.5a.5.5 0 0 0-.024.937l7.93 3.18a2 2 0 0 1 1.112 1.11z"
                  fill="none" stroke="currentColor"></path>
                <path d="m21.854 2.147-10.94 10.939" fill="none" stroke="currentColor"></path>
              </svg>
              {{ __('Send') }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<div class="modal fade" tabindex="-1" id="" aria-labelledby="" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Modal title</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">
          {{ __('Close') }}
        </button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>

  let run = 0;
  let elThinkingDots = null;

  const toggleInput = (enable) => {

    const elInputField = document.querySelector('.tw-chat-footer-input');
    elInputField.disabled = !enable;

    if (enable) {
      elInputField.focus();
    }
  };

  const toggleButtons = (enable) => {

    const elInputField = document.querySelector('.tw-chat-footer-input');
    const elUploadButton = document.querySelector('.tw-chat-footer-upload');
    const elSendButton = document.querySelector('.tw-chat-footer-send');

    if (enable) {
      elSendButton.disabled = false;
      elSendButton.classList.remove('tw-disabled');
    } else {
      const message = elInputField.value.trim();
      if (message === '') {
        elSendButton.disabled = true;
        elSendButton.classList.add('tw-disabled');
      }
    }

    elUploadButton.disabled = true;
    elUploadButton.classList.add('tw-disabled');
  };

  const actions = ["Chargement du contexte...", "Analyse de votre demande...", "Recherche d'informations...",
    "Assemblage des informations recueillies...", "Génération de la réponse...",
    "Un instant, nous y sommes presque..."];
  let actionIndex = 0;
  let loadingInterval = null;

  const addThinkingDots = () => {

    run++;

    setTimeout(() => {
      if (run > 0) {

        elThinkingDots = document.createElement('div');
        elThinkingDots.classList.add('tw-answer-wrapper');
        elThinkingDots.innerHTML = `
          <div class="tw-answer">
            <div class="tw-answer-avatar-wrapper">
              <div class="tw-answer-avatar">
                <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"
                      stroke="currentColor"  stroke-width="1"  stroke-linecap="round"  stroke-linejoin="round" class="tw-avatar-color">
                  <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                  <path d="M6 4m0 2a2 2 0 0 1 2 -2h8a2 2 0 0 1 2 2v4a2 2 0 0 1 -2 2h-8a2 2 0 0 1 -2 -2z" />
                  <path d="M12 2v2" />
                  <path d="M9 12v9" />
                  <path d="M15 12v9" />
                  <path d="M5 16l4 -2" />
                  <path d="M15 14l4 2" />
                  <path d="M9 18h6" />
                  <path d="M10 8v.01" />
                  <path d="M14 8v.01" />
                </svg>
              </div>
            </div>
            <!-- <div class="typing-wrapper">
              <div class="typing">
                <span></span>
                <span></span>
                <span></span>
              </div>
            </div> -->
            <div class="tw-answer-message">
              <div class="tw-answer-message-html" style="color: var(--bs-gray);">
                ${actions[actionIndex++]}
              </div>
            </div>
          </div>
      `;

        loadingInterval = setInterval(() => {
          if (elThinkingDots && actionIndex < actions.length) {
            const workInProgressEl = elThinkingDots.querySelector('.tw-answer-message-html');
            if (workInProgressEl) {
              workInProgressEl.innerHTML = actions[actionIndex++];
            }
          }
        }, 5000);

        toggleButtons(false);
        toggleInput(false);

        const elConversation = document.querySelector('.tw-conversation');
        elConversation.appendChild(elThinkingDots);
        elConversation.scrollTop = elConversation.scrollHeight; // scroll to bottom
      }
    }, 500);
  };

  const removeThinkingDots = () => {
    if (elThinkingDots) {

      if (loadingInterval) {
        clearInterval(loadingInterval);
        loadingInterval = null;
        actionIndex = 0;
      }

      elThinkingDots.remove();
      elThinkingDots = null;

      toggleInput(true);
      toggleButtons(true);
    }
    run--;
  };

  const addUserDirective = (ts, directive) => {

    const formatTimestamp = (timestamp) => {
      const date = new Date(timestamp);
      const year = date.getFullYear();
      const month = String(date.getMonth() + 1).padStart(2, '0'); // Months are zero-based
      const day = String(date.getDate()).padStart(2, '0');
      const hours = String(date.getHours()).padStart(2, '0');
      const minutes = String(date.getMinutes()).padStart(2, '0');
      return `${year}-${month}-${day} ${hours}:${minutes}`;
    };

    const elInputField = document.querySelector('.tw-chat-footer-input');
    if (elInputField.value.trim() !== '') {
      const elActions = document.querySelector('.tw-actions');
      elActions.style.display = 'none';
    }

    const elDirective = document.createElement('div');
    elDirective.classList.add('tw-question-wrapper');
    elDirective.innerHTML = `
      <div class="tw-question">
        <div class="tw-question-avatar-wrapper">
          <div class="tw-question-avatar">
              <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"
                    stroke="currentColor"  stroke-width="1"  stroke-linecap="round"  stroke-linejoin="round" class="tw-avatar-color">
                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                <path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0"/>
                <path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/>
              </svg>
          </div>
        </div>
        <div class="tw-question-directive">${directive}</div>
      </div>
      <div class="tw-question-timestamp">${formatTimestamp(ts)}</div>
    `;

    const elConversation = document.querySelector('.tw-conversation');
    elConversation.appendChild(elDirective);
    elConversation.scrollTop = elConversation.scrollHeight; // scroll to bottom
  };

  const addBotAnswer = (ts, answer) => {

    const formatTimestamp = (timestamp) => {
      const date = new Date(timestamp);
      const year = date.getFullYear();
      const month = String(date.getMonth() + 1).padStart(2, '0'); // Months are zero-based
      const day = String(date.getDate()).padStart(2, '0');
      const hours = String(date.getHours()).padStart(2, '0');
      const minutes = String(date.getMinutes()).padStart(2, '0');
      return `${year}-${month}-${day} ${hours}:${minutes}`;
    };

    const paragraphs = answer.response.map(line => `<p class="tw-answer-message-paragraph">${line}</p>`).join('');
    const html = answer.html.trim() !== '' ? `<div class="tw-answer-message-html">${answer.html}</div>` : '';
    const uid = com.computablefacts.helpers.goodFastHash(answer.chain_of_thought);

    const elDirective = document.createElement('div');
    elDirective.classList.add('tw-answer-wrapper');
    elDirective.innerHTML = `
      <div class="tw-answer">
        <div id="cot-${uid}" style="display:none">${JSON.stringify(answer.chain_of_thought)}</div>
        <div class="tw-answer-avatar-wrapper" onclick="chainOfThought('cot-${uid}')">
          <div class="tw-answer-avatar">
            <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"
                  stroke="currentColor"  stroke-width="1"  stroke-linecap="round"  stroke-linejoin="round" class="tw-avatar-color">
              <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
              <path d="M6 4m0 2a2 2 0 0 1 2 -2h8a2 2 0 0 1 2 2v4a2 2 0 0 1 -2 2h-8a2 2 0 0 1 -2 -2z" />
              <path d="M12 2v2" />
              <path d="M9 12v9" />
              <path d="M15 12v9" />
              <path d="M5 16l4 -2" />
              <path d="M15 14l4 2" />
              <path d="M9 18h6" />
              <path d="M10 8v.01" />
              <path d="M14 8v.01" />
            </svg>
          </div>
        </div>
        <div class="tw-answer-message">
          ${paragraphs}
          ${html}
        </div>
      </div>
      <div class="tw-answer-timestamp">${formatTimestamp(ts)}</div>
    `;

    const elConversation = document.querySelector('.tw-conversation');
    elConversation.appendChild(elDirective);
    elConversation.scrollTop = elConversation.scrollHeight; // scroll to bottom
  };

  const askQuestion = () => {

    const elInputField = document.querySelector('.tw-chat-footer-input');
    const directive = elInputField.value.trim();

    if (directive && run === 0) {

      addThinkingDots();
      addUserDirective(new Date(), directive);

      elInputField.value = '';

      askCyberBuddyApiCall('{{ $conversation->thread_id }}', directive, (response) => {
        if (response) {
          addBotAnswer(new Date(), response);
        } else {
          console.log(response);
        }
      }, () => removeThinkingDots());
    }
  };

  document.addEventListener('DOMContentLoaded', () => {

    const elActions = document.querySelector('.tw-actions');
    const messages = @json($conversation->lightThread());
    if (elActions && messages.length <= 0) {
      elActions.style.display = 'unset';
    }
    messages.forEach(message => {
      if (message.role === 'user') {
        addUserDirective(message.timestamp ? new Date(message.timestamp) : new Date(), message.content);
      } else if (message.role === 'assistant') {
        addBotAnswer(message.timestamp ? new Date(message.timestamp) : new Date(),
          {response: [], html: message.html, chain_of_thought: message.chain_of_thought});
      } else {
        console.log('unknown message type', message);
      }
    });

    const elInputField = document.querySelector('.tw-chat-footer-input');
    const elSendButton = document.querySelector('.tw-chat-footer-send');

    toggleButtons(false); // Initially, disable buttons

    elInputField.addEventListener('focus', () => toggleButtons(true));
    elInputField.addEventListener('blur', () => toggleButtons(false));
    elSendButton.addEventListener('click', askQuestion);
    elInputField.addEventListener('input', () => {
      if (elActions) {
        if (elInputField.value.trim() !== '') {
          elActions.style.display = 'none';
        } else if (elActions.style.display == 'none') {
          const elQuestions = document.querySelectorAll('.tw-question');
          const elAnswers = document.querySelectorAll('.tw-answer');
          if (elQuestions.length === 0 && elAnswers.length === 0) {
            elActions.style.display = 'unset';
          }
        }
      }
    });
    elInputField.addEventListener('keydown', (event) => {
      if (event.key === 'Enter') {
        event.preventDefault();
        askQuestion();
      }
    });
    elInputField.focus();
  });

  const chainOfThought = (uid) => {

    const elCot = document.getElementById(uid);
    const content = elCot.innerText;

    if (content !== 'undefined') {

      const cot = JSON.parse(content);
      const modal = new bootstrap.Modal(document.querySelector('.modal'));
      const modalTitle = document.querySelector('.modal-title');
      const modalBody = document.querySelector('.modal-body');

      modalTitle.textContent = "{{ __('Chain of Thought') }}";
      modalBody.innerHTML = cot.map(c => `
        <p><b>Thought.</b> ${c.thought}</p>
        <p><b>Action.</b> ${c.action}</p>
        <p><b>Observation.</b> ${c.observation}</p>
      `).join('');

      modal.show();
    }
  };

</script>
@endpush

