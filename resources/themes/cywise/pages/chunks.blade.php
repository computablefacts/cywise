<?php

use App\Http\Controllers\Iframes\ChunksController;
use App\Http\Middleware\CheckPermissionsHttpRequest;
use App\Http\Middleware\LogHttpRequests;
use Illuminate\Http\Request;
use function Laravel\Folio\{middleware, name, render};

middleware([LogHttpRequests::class, 'auth', CheckPermissionsHttpRequest::class]);
name('chunks');
render(function (Request $request) {
  return app(ChunksController::class)($request);
});
?>

<x-layouts.app>
  <div class="ui:mx-auto ui:flex ui:w-full ui:max-w-7xl ui:flex-col ui:gap-6 ui:px-4 ui:py-8 ui:lg:px-8">

    <x-ui.page-header :title="__('Chunks')"
                      :subtitle="__('Extracts of your documents used by CyberBuddy. Click a text to edit it.')"/>

    {{-- Filtered by document or collection (links from the documents and collections pages) --}}
    @if($file || $collection)
      <div class="ui:flex ui:flex-wrap ui:items-center ui:justify-between ui:gap-3 ui:rounded-xl ui:border ui:border-solid ui:border-blue-200 ui:bg-info-soft ui:px-4 ui:py-3 ui:text-sm ui:text-blue-900">
        <span class="ui:flex ui:items-center ui:gap-2">
          <x-phosphor-funnel class="ui:size-4 ui:shrink-0"/>
          @if($file)
            {{ __('Only the chunks from the ":document" document are displayed.', ['document' => $file]) }}
          @else
            {{ __('Only the chunks from the ":collection" collection are displayed.', ['collection' => $collection]) }}
          @endif
        </span>
        <x-ui.button variant="ghost" size="sm" :href="route('chunks')">{{ __('Reset filters') }}</x-ui.button>
      </div>
    @endif

    <x-ui.card flush>
      @if($chunks->isEmpty())
        <x-ui.empty icon="grid-four">{{ __('No chunk.') }}</x-ui.empty>
      @else
        <x-ui.table>
          <thead>
          <tr>
            <th class="ui:w-px"></th>
            <th>{{ __('Collection') }}</th>
            <th>{{ __('Filename') }}</th>
            <th class="ui:text-right!">{{ __('Page') }}</th>
            <th class="ui:text-right!">{{ __('Length') }}</th>
            <th class="ui:text-right!">{{ __('Number of Vectors') }}</th>
            <th>{{ __('Created At') }}</th>
            <th></th>
          </tr>
          </thead>
          @foreach($chunks as $chunk)
            {{-- One tbody per chunk: the row and its expandable text share the Alpine state --}}
            <tbody x-data="{ isExpanded: false }">
            <tr>
              <td>
                <span title="{{ $chunk->isEmbedded() ? __('Embedded') : __('Not embedded') }}"
                      class="ui:block ui:size-2.5 ui:rounded-full {{ $chunk->isEmbedded() ? 'ui:bg-low' : 'ui:bg-high' }}"></span>
              </td>
              <td><x-ui.tag tone="auto">{{ $chunk->collection->name }}</x-ui.tag></td>
              <td>
                <div class="ui:flex ui:flex-col ui:gap-1.5">
                  <a href="{{ $chunk->file->downloadUrl() }}" class="ui:font-medium ui:text-ink! ui:hover:text-brand-600!">
                    {{ $chunk->file->name_normalized }}.{{ $chunk->file->extension }}
                  </a>
                  @php $tags = $chunk->tags()->orderBy('id')->get(); @endphp
                  @if($tags->isNotEmpty())
                    <div class="ui:flex ui:flex-wrap ui:gap-1">
                      @foreach($tags as $tag)
                        <x-ui.tag>{{ $tag->tag }}</x-ui.tag>
                      @endforeach
                    </div>
                  @endif
                </div>
              </td>
              <td class="ui:text-right ui:tabular-nums">
                <a href="{{ $chunk->file->downloadUrl() }}?page={{ $chunk->page }}" class="ui:text-brand-600! ui:hover:text-brand-700!">
                  {{ $chunk->page }}
                </a>
              </td>
              <td class="ui:text-right ui:tabular-nums">
                {{ Illuminate\Support\Number::format(\Illuminate\Support\Str::length($chunk->text), locale:'sv') }}
              </td>
              <td class="ui:text-right ui:tabular-nums">
                {{ Illuminate\Support\Number::format($chunk->vectors()->count(), locale:'sv') }}
              </td>
              <td class="ui:whitespace-nowrap ui:text-slate-500">{{ $chunk->created_at->format('Y-m-d H:i') }}</td>
              <td>
                <div class="ui:flex ui:items-center ui:justify-end ui:gap-1.5">
                  <x-ui.icon-button icon="trash" :title="__('Delete')" onclick="deleteChunk({{ $chunk->id }})"/>
                  <x-ui.icon-button icon="caret-right" :title="__('Show the text')" @click="isExpanded = !isExpanded"
                                    ::aria-expanded="isExpanded" ::class="isExpanded && 'ui:rotate-90'"/>
                </div>
              </td>
            </tr>
            <tr x-show="isExpanded" x-cloak>
              <td colspan="8" class="ui:bg-slate-50">
                <pre class="ui:m-0 ui:max-h-96 ui:overflow-auto ui:whitespace-pre-wrap ui:wrap-anywhere ui:rounded-lg ui:border ui:border-solid ui:border-line ui:bg-white ui:p-3 ui:font-mono ui:text-xs ui:cursor-text ui:text-slate-500"
                     title="{{ __('Click to edit') }}"
                     onclick="editChunk(this, {{ $chunk->id }})">{{ $chunk->text }}</pre>
              </td>
            </tr>
            </tbody>
          @endforeach
        </x-ui.table>
        <x-ui.pagination :page="$currentPage" :pages="$nbPages"
                         :url="fn($page) => route('chunks', ['page' => $page, 'collection' => $collection])"
                         class="ui:border-0 ui:border-t ui:border-solid ui:border-line"/>
      @endif
    </x-ui.card>
  </div>
  <script>

    // Read-only texts are greyed out; the class is removed while editing
    const READ_ONLY_CLASS = 'ui:text-slate-500';

    function deleteChunk(chunkId) {

      const response = confirm("{{ __('Are you sure you want to delete this chunk?') }}");

      if (response) {
        deleteChunkApiCall(chunkId);
      }
    }

    function editChunk(pre, chunkId) {

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
        saveChunk(pre, chunkId, originalText, pre.innerText);
      }
    }

    function saveChunk(pre, chunkId, oldValue, newValue) {
      if (oldValue.trim() !== newValue.trim()) {

        const response = confirm("{{ __('Are you sure you want to edit this chunk?') }}");

        if (!response) {
          pre.innerText = oldValue;
        } else {
          updateChunkApiCall(chunkId, newValue);
        }
      }
    }

  </script>
</x-layouts.app>
