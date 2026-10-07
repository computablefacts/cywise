<?php

use App\Http\Controllers\Iframes\PromptsController;
use App\Http\Middleware\CheckPermissionsHttpRequest;
use App\Http\Middleware\LogHttpRequests;
use Illuminate\Http\Request;
use function Laravel\Folio\{middleware, name, render};

middleware([LogHttpRequests::class, 'auth', CheckPermissionsHttpRequest::class]);
name('prompts');
render(function (Request $request) {
  return app(PromptsController::class)($request);
});
?>

<x-layouts.app>
  <div class="ui:mx-auto ui:flex ui:w-full ui:max-w-7xl ui:flex-col ui:gap-6 ui:px-4 ui:py-8 ui:lg:px-8">

    <x-ui.page-header :title="__('Prompts')"
                      :subtitle="__('Templates of the instructions sent to the AI. Click a template to edit it.')"/>

    <x-ui.card flush>
      @if($prompts->isEmpty())
        <x-ui.empty icon="notepad">{{ __('No prompt.') }}</x-ui.empty>
      @else
        <x-ui.table>
          <thead>
          <tr>
            <th>{{ __('Name') }}</th>
            <th class="ui:text-right!">{{ __('Length') }}</th>
            <th>{{ __('Created At') }}</th>
            <th>{{ __('Created By') }}</th>
            <th></th>
          </tr>
          </thead>
          @foreach($prompts as $prompt)
            {{-- One tbody per prompt: the row and its expandable template share the Alpine state --}}
            <tbody x-data="{ isExpanded: false }">
            <tr>
              <td class="ui:font-medium">{{ $prompt->name }}</td>
              <td class="ui:text-right ui:tabular-nums">
                {{ Illuminate\Support\Number::format(\Illuminate\Support\Str::length($prompt->template), locale:'sv') }}
              </td>
              <td class="ui:whitespace-nowrap ui:text-slate-500">{{ $prompt->created_at->format('Y-m-d H:i') }}</td>
              <td class="ui:text-slate-500">{{ $prompt->createdBy->name }}</td>
              <td>
                <div class="ui:flex ui:items-center ui:justify-end ui:gap-1.5">
                  <x-ui.icon-button icon="trash" :title="__('Delete')" onclick="deletePrompt({{ $prompt->id }})"/>
                  <x-ui.icon-button icon="caret-right" :title="__('Show the text')" @click="isExpanded = !isExpanded"
                                    ::aria-expanded="isExpanded" ::class="isExpanded && 'ui:rotate-90'"/>
                </div>
              </td>
            </tr>
            <tr x-show="isExpanded" x-cloak>
              <td colspan="5" class="ui:bg-slate-50">
                <pre class="ui:m-0 ui:max-h-96 ui:overflow-auto ui:whitespace-pre-wrap ui:wrap-anywhere ui:rounded-lg ui:border ui:border-solid ui:border-line ui:bg-white ui:p-3 ui:font-mono ui:text-xs ui:cursor-text ui:text-slate-500"
                     title="{{ __('Click to edit') }}"
                     onclick="editPrompt(this, {{ $prompt->id }})">{{ $prompt->template }}</pre>
              </td>
            </tr>
            </tbody>
          @endforeach
        </x-ui.table>
        <x-ui.pagination :page="$currentPage" :pages="$nbPages"
                         :url="fn($page) => route('prompts', ['page' => $page])"
                         class="ui:border-0 ui:border-t ui:border-solid ui:border-line"/>
      @endif
    </x-ui.card>
  </div>
  <script>

    // Read-only templates are greyed out; the class is removed while editing
    const READ_ONLY_CLASS = 'ui:text-slate-500';

    function deletePrompt(promptId) {

      const response = confirm("{{ __('Are you sure you want to delete this prompt?') }}");

      if (response) {
        deletePromptApiCall(promptId);
      }
    }

    function editPrompt(pre, promptId) {

      if (pre.getAttribute('contenteditable') === 'true') {
        return; // Prevent multiple edits
      }

      const originalText = pre.innerText;

      pre.classList.toggle(READ_ONLY_CLASS);
      pre.setAttribute('contenteditable', 'true');
      pre.focus();
      pre.onblur = () => {
        pre.removeAttribute('contenteditable');
        pre.classList.toggle(READ_ONLY_CLASS);
        savePrompt(pre, promptId, originalText, pre.innerText);
      }
    }

    function savePrompt(pre, promptId, oldValue, newValue) {
      if (oldValue.trim() !== newValue.trim()) {

        const response = confirm("{{ __('Are you sure you want to edit this prompt?') }}");

        if (!response) {
          pre.innerText = oldValue;
        } else {
          updatePromptApiCall(promptId, newValue);
        }
      }
    }

  </script>
</x-layouts.app>
