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
     * Chat bubbles are built by the JS below (addUserDirective, addBotAnswer, addThinkingDots)
     * and styled with ui: utilities. The rules left here style what utilities cannot reach:
     * JS state (.tw-disabled) and the answer HTML returned by the backend.
     */

    .tw-disabled {
      opacity: 0.5 !important;
      pointer-events: none;
    }

    /* ANSWER HTML (headings, paragraphs, lists) */

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

    /* LEGACY ANSWER HTML: stored conversations may still contain these blocks */

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

      <div class="tw-conversation-wrapper ui:min-h-0 ui:flex-1 ui:overflow-hidden">
        <div class="tw-conversation ui:h-full ui:overflow-y-auto ui:p-4">
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

  {{-- Chain of thought (native dialog, opened from the bot avatar by chainOfThought; .cot-body filled by JS) --}}
  <dialog id="cot-dialog" aria-labelledby="cot-dialog-title"
          class="ui:w-[calc(100%-2rem)] ui:max-w-lg ui:overflow-hidden ui:rounded-xl ui:border ui:border-solid ui:border-line ui:bg-surface ui:p-0 ui:text-ink ui:shadow-xl ui:backdrop:bg-slate-900/40">
    <header class="ui:flex ui:items-center ui:justify-between ui:gap-4 ui:border-0 ui:border-b ui:border-solid ui:border-line ui:px-5 ui:py-4">
      <h2 id="cot-dialog-title" class="ui:m-0 ui:text-base ui:font-semibold ui:text-ink">{{ __('Chain of Thought') }}</h2>
      <x-ui.icon-button icon="x" :title="__('Close')" onclick="this.closest('dialog').close()"/>
    </header>
    <div class="cot-body ui:max-h-[60vh] ui:overflow-y-auto ui:overflow-x-hidden ui:px-5 ui:py-4 ui:text-sm ui:leading-relaxed ui:text-slate-700"></div>
    <footer class="ui:flex ui:justify-end ui:border-0 ui:border-t ui:border-solid ui:border-line ui:px-5 ui:py-3">
      <x-ui.button onclick="this.closest('dialog').close()">{{ __('Close') }}</x-ui.button>
    </footer>
  </dialog>
  <script>

    // Bubble avatars, rendered server side (phosphor) and reused by the templates below
    const BOT_ICON = `<x-phosphor-robot class="ui:size-6"/>`;
    const USER_ICON = `<x-phosphor-user class="ui:size-6"/>`;
    const BOT_AVATAR_CLASSES = 'tw-answer-avatar ui:flex ui:rounded-full ui:bg-brand-50 ui:p-2 ui:text-brand-500';
    const ANSWER_CLASSES = ['tw-answer-wrapper', 'ui:mb-5', 'ui:flex', 'ui:flex-col'];
    const MESSAGE_CLASSES = 'tw-answer-message ui:min-w-0 ui:max-w-[85%] ui:rounded-2xl ui:rounded-bl-sm ui:border ui:border-solid ui:border-line ui:bg-white ui:px-4 ui:py-3 ui:text-sm ui:leading-relaxed ui:text-ink';

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

    @php
      // Blade can't parse a multi-line @json([...]): build the array first.
      $loadingMessages = [
        __('Loading context...'), __('Analyzing your request...'), __('Searching for information...'),
        __('Assembling the collected information...'), __('Generating the answer...'),
        __('One moment, almost there...'),
      ];
    @endphp
    const actions = @json($loadingMessages);
    let actionIndex = 0;
    let loadingInterval = null;

    const addThinkingDots = () => {

      run++;

      setTimeout(() => {
        if (run > 0) {

          elThinkingDots = document.createElement('div');
          elThinkingDots.classList.add(...ANSWER_CLASSES);
          elThinkingDots.innerHTML = `
          <div class="tw-answer ui:flex ui:items-start ui:gap-3">
            <div class="tw-answer-avatar-wrapper">
              <div class="${BOT_AVATAR_CLASSES}">${BOT_ICON}</div>
            </div>
            <div class="${MESSAGE_CLASSES}">
              <div class="tw-answer-message-html ui:text-slate-500">
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
      elDirective.classList.add('tw-question-wrapper', 'ui:mb-5', 'ui:flex', 'ui:flex-col');
      elDirective.innerHTML = `
      <div class="tw-question ui:flex ui:flex-row-reverse ui:items-start ui:gap-3">
        <div class="tw-question-avatar-wrapper">
          <div class="tw-question-avatar ui:flex ui:rounded-full ui:bg-slate-100 ui:p-2 ui:text-slate-600">${USER_ICON}</div>
        </div>
        <div class="tw-question-directive ui:max-w-[75%] ui:whitespace-pre-wrap ui:rounded-2xl ui:rounded-br-sm ui:bg-ink ui:px-4 ui:py-3 ui:text-sm ui:leading-normal ui:text-white">${htmlEscape(directive)}</div>
      </div>
      <div class="tw-question-timestamp ui:pr-13 ui:pt-1.5 ui:text-right ui:text-xs ui:text-slate-400">${formatTimestamp(ts)}</div>
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

      const paragraphs = answer.response.map(line => `<p class="tw-answer-message-paragraph ui:mb-2">${line}</p>`).join('');
      const html = answer.html.trim() !== '' ? `<div class="tw-answer-message-html">${answer.html}</div>` : '';
      const uid = com.computablefacts.helpers.goodFastHash(answer.chain_of_thought);

      const elDirective = document.createElement('div');
      elDirective.classList.add(...ANSWER_CLASSES);
      elDirective.innerHTML = `
      <div class="tw-answer ui:flex ui:items-start ui:gap-3">
        <div id="cot-${uid}" style="display:none">${JSON.stringify(answer.chain_of_thought)}</div>
        <div class="tw-answer-avatar-wrapper ui:cursor-pointer" onclick="chainOfThought('cot-${uid}')">
          <div class="${BOT_AVATAR_CLASSES}">${BOT_ICON}</div>
        </div>
        <div class="${MESSAGE_CLASSES}">
          ${paragraphs}
          ${html}
        </div>
      </div>
      <div class="tw-answer-timestamp ui:flex ui:items-center ui:gap-2 ui:pl-13 ui:pt-1.5 ui:text-xs ui:text-slate-400">
        ${formatTimestamp(ts)}
        <button type="button" class="tw-save-memo-btn ui:flex ui:cursor-pointer ui:border-0 ui:bg-transparent ui:p-0 ui:text-slate-400 ui:hover:text-brand-500 ui:disabled:opacity-50"
                title="{{ __('Save as note') }}" aria-label="{{ __('Save as note') }}">
          <x-phosphor-note-pencil class="ui:size-4"/>
        </button>
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
        const elDialog = document.getElementById('cot-dialog');
        const elBody = elDialog.querySelector('.cot-body');

        // One block per step: thought, action, observation
        elBody.innerHTML = cot.map(c => `
        <div class="ui:mb-4 ui:flex ui:flex-col ui:gap-1 ui:last:mb-0">
          <p class="ui:m-0"><b>{{ __('Thought.') }}</b> ${c.thought}</p>
          <p class="ui:m-0"><b>{{ __('Action.') }}</b> ${c.action}</p>
          <p class="ui:m-0"><b>{{ __('Observation.') }}</b> ${c.observation}</p>
        </div>
      `).join('');

        elDialog.showModal();
      }
    };

  </script>
</x-layouts.app>

