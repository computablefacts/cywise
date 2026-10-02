<?php

use App\Http\Controllers\Iframes\CyberBuddyController;
use App\Http\Middleware\CheckPermissionsHttpRequest;
use App\Http\Middleware\LogHttpRequests;
use Illuminate\Http\Request;
use function Laravel\Folio\{middleware, name, render};

middleware([LogHttpRequests::class, 'auth', CheckPermissionsHttpRequest::class]);
name('cyberbuddy');
render(function (Request $request) {
  return app(CyberBuddyController::class)($request);
});
?>

<x-layouts.app>
  {{-- $conversation: resumed (?conversation_id=) or created by CyberBuddyController --}}

  @push('styles')
  <style>

    /*
     * Chat bubbles are built by the JS below (addUserDirective, addBotAnswer, addThinkingDots).
     * They keep their tw-* classes and are styled here with the design tokens.
     */

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

    /* QUESTION (user, right) */

    .tw-question-wrapper {
      display: flex;
      flex-direction: column;
      margin-bottom: 1.25rem;
    }

    .tw-question {
      display: flex;
      flex-direction: row-reverse;
      align-items: flex-start;
      gap: .75rem;
    }

    .tw-question-avatar {
      display: flex;
      padding: .5rem;
      border-radius: 9999px;
      background-color: #f1f5f9;
    }

    .tw-question-avatar .tw-avatar-color {
      color: #475569;
    }

    .tw-question-directive {
      max-width: 75%;
      padding: .75rem 1rem;
      border-radius: 1rem 1rem .25rem 1rem;
      background-color: var(--ui-color-ink);
      color: #fff;
      font-size: .875rem;
      line-height: 1.5;
      white-space: pre-wrap;
    }

    .tw-question-timestamp {
      padding-top: .375rem;
      padding-right: 3.25rem;
      text-align: right;
      font-size: .75rem;
      color: #94a3b8;
    }

    /* ANSWER (bot, left). Clicking the avatar opens the chain of thought. */

    .tw-answer-wrapper {
      display: flex;
      flex-direction: column;
      margin-bottom: 1.25rem;
    }

    .tw-answer {
      display: flex;
      align-items: flex-start;
      gap: .75rem;
    }

    .tw-answer-avatar-wrapper {
      cursor: pointer;
    }

    .tw-answer-avatar {
      display: flex;
      padding: .5rem;
      border-radius: 9999px;
      background-color: var(--ui-color-brand-50);
    }

    .tw-avatar-color {
      color: var(--ui-color-brand-500);
    }

    .tw-answer-message {
      min-width: 0;
      max-width: 85%;
      padding: .75rem 1rem;
      border: 1px solid var(--ui-color-line);
      border-radius: 1rem 1rem 1rem .25rem;
      background-color: #fff;
      color: var(--ui-color-ink);
      font-size: .875rem;
      line-height: 1.6;
    }

    .tw-answer-message-paragraph {
      margin-bottom: .5rem;
    }

    .tw-answer-message-html {
      --font-size: 14px;
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
      color: inherit;
      margin-bottom: .5rem;
    }

    /* Lists: restore markers removed by the CSS reset */
    .tw-answer-message-html ul,
    .tw-answer-message-html ol {
      margin: .25rem 0 .5rem;
      padding-left: 1.25rem;
    }

    .tw-answer-message-html ul {
      list-style: disc;
    }

    .tw-answer-message-html ol {
      list-style: decimal;
    }

    .tw-answer-message-html p:last-child {
      margin-bottom: 0;
    }

    .tw-answer-timestamp {
      padding-top: .375rem;
      padding-left: 3.25rem;
      font-size: .75rem;
      color: #94a3b8;
    }

    .tw-save-memo-btn:hover {
      color: var(--ui-color-brand-500);
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

  {{--
    ┌─────────────────────────────────────┬───────────────┐
    │ header           [New conversation] │ recent        │
    ├─────────────────────────────────────┤ conversations │
    │ conversation (JS) / suggestions     │ (xl+)         │
    ├─────────────────────────────────────┤               │
    │ input  [image] [send]               │               │
    └─────────────────────────────────────┴───────────────┘
    Full height: dynamic viewport (mobile address bar aware) minus the app topbar (4rem).
  --}}
  <div class="ui:flex ui:h-[calc(100dvh-4rem)] ui:gap-6 ui:p-4 ui:lg:p-6">

    {{-- Chat. JS hooks: .tw-conversation, .tw-chat-footer-input, .tw-chat-footer-upload, .tw-chat-footer-send --}}
    <section class="ui:flex ui:min-w-0 ui:flex-1 ui:flex-col ui:overflow-hidden ui:rounded-xl ui:border ui:border-solid ui:border-line ui:bg-surface ui:shadow-xs">

      <header class="ui:flex ui:items-center ui:gap-3 ui:border-0 ui:border-b ui:border-solid ui:border-line ui:px-5 ui:py-4">
        <span class="ui:flex ui:size-10 ui:shrink-0 ui:items-center ui:justify-center ui:rounded-xl ui:bg-brand-500 ui:text-white">
          <x-phosphor-robot-bold class="ui:size-5"/>
        </span>
        <div class="ui:min-w-0 ui:flex-1">
          <h1 class="ui:m-0 ui:text-lg ui:font-semibold ui:leading-6 ui:text-ink">{{ tenant_custom_text('CyberBuddy') }}</h1>
          <p class="ui:m-0 ui:truncate ui:text-sm ui:text-slate-500">{{ __('Your AI cybersecurity assistant') }}</p>
        </div>
        <x-ui.button variant="secondary" size="sm" :href="route('cyberbuddy')">
          <x-phosphor-plus class="ui:size-4"/>
          <span class="ui:hidden ui:sm:inline">{{ __('New conversation') }}</span>
        </x-ui.button>
      </header>

      <div class="tw-conversation-wrapper">
        <div class="tw-conversation">
          <!-- DYNAMICALLY FILLED -->
          @include('theme::iframes.cyberbuddy._actions')
        </div>
      </div>

      <footer class="ui:border-0 ui:border-t ui:border-solid ui:border-line ui:px-5 ui:py-4">
        <div class="ui:flex ui:items-center ui:gap-2">
          <input value="" type="text" placeholder="{{ __('Ask me anything!') }}"
                 class="tw-chat-footer-input ui:h-10 ui:min-w-0 ui:flex-1 ui:rounded-lg ui:border ui:border-solid ui:border-line ui:bg-slate-50 ui:px-4 ui:text-sm ui:text-ink ui:placeholder:text-slate-400 ui:focus:border-brand-500 ui:focus:bg-white ui:focus:outline-none ui:focus:ring-2 ui:focus:ring-brand-100 ui:disabled:opacity-60"/>
          <button type="button"
                  class="tw-chat-footer-upload ui:flex ui:size-10 ui:shrink-0 ui:items-center ui:justify-center ui:rounded-lg ui:border-0 ui:bg-transparent ui:text-slate-500 ui:hover:bg-slate-100">
            <x-phosphor-image class="ui:size-5"/>
          </button>
          <x-ui.button class="tw-chat-footer-send" icon="paper-plane-right">{{ __('Send') }}</x-ui.button>
        </div>
        <p class="ui:m-0 ui:mt-2 ui:text-center ui:text-xs ui:text-slate-400">
          {{ __('Please ensure that you do not enter any sensitive or confidential information in your requests.') }}
        </p>
      </footer>
    </section>

    {{-- Recent conversations --}}
    <aside class="ui:hidden ui:w-80 ui:shrink-0 ui:flex-col ui:overflow-y-auto ui:xl:flex">
      <x-ui.card flush :title="__('Recent conversations')"
                 :href="Auth::user()->canView('iframes.conversations') ? route('conversations') : null">
        @if($recentConversations->isEmpty())
          <x-ui.empty icon="chats">{{ __('No conversation yet.') }}</x-ui.empty>
        @else
          <ul class="ui:m-0 ui:list-none ui:p-0">
            @foreach($recentConversations as $recent)
              <li class="ui:border-0 ui:border-t ui:border-solid ui:border-line">
                <a href="{{ route('cyberbuddy', ['conversation_id' => $recent->id]) }}"
                   class="ui:flex ui:items-start ui:gap-3 ui:px-5 ui:py-3 ui:no-underline! ui:hover:bg-slate-50">
                  <x-phosphor-chat-circle class="ui:mt-0.5 ui:size-4 ui:shrink-0 ui:text-brand-500"/>
                  <span class="ui:flex ui:min-w-0 ui:flex-col">
                    <span class="ui:line-clamp-2 ui:text-sm ui:text-ink!">{{ $recent->description }}</span>
                    <span class="ui:text-xs ui:text-slate-400!">{{ $recent->created_at->diffForHumans() }}</span>
                  </span>
                </a>
              </li>
            @endforeach
          </ul>
        @endif
      </x-ui.card>
    </aside>
  </div>

  {{-- Chain of thought (Bootstrap modal, opened from the bot avatar) --}}
  <div class="modal fade" tabindex="-1" id="" aria-labelledby="" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Modal title</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body" style="max-height:60vh;overflow-y:auto;overflow-x:hidden"></div>
        <div class="modal-footer">
          <button type="button" class="btn btn-primary" data-bs-dismiss="modal">
            {{ __('Close') }}
          </button>
        </div>
      </div>
    </div>
  </div>
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

      const htmlEscape = (d) => d.replace(/[&<>"']/g, (match) => {
        const escape = {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'};
        return escape[match];
      });

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
        <div class="tw-question-directive">${htmlEscape(directive)}</div>
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
      <div class="tw-answer-timestamp" style="display:flex;gap:0.5rem;">
        ${formatTimestamp(ts)}
        <div class="tw-save-memo-btn" style="cursor:pointer;" title="{{ __('Save as note') }}">
          <span class="bp4-icon bp4-icon-manually-entered-data"></span>
        </div>
      </div>
    `;

      // Wire save button
      const elSaveAsNote = elDirective.querySelector('.tw-save-memo-btn');
      if (elSaveAsNote) {
        elSaveAsNote.addEventListener('click', (e) => {
          const elWrapper = e.currentTarget.closest('.tw-answer-wrapper');
          if (!elWrapper) {
            return;
          }
          const elMessage = elWrapper.querySelector('.tw-answer-message');
          if (!elMessage) {
            return;
          }
          let text = elMessage.innerText || '';
          text = (text || '').trim();
          if (!text) {
            return;
          }
          if (text.length > 1000) {
            text = text.substring(0, 1000);
          }
          elSaveAsNote.disabled = true;
          createNoteApiCall(text, (result) => {
            onSuccessDefault(result);
            elSaveAsNote.disabled = false;
          });
        });
      }

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
</x-layouts.app>

