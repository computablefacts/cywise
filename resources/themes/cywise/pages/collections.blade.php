<?php

use App\Http\Controllers\Iframes\CollectionsController;
use App\Http\Middleware\CheckPermissionsHttpRequest;
use App\Http\Middleware\LogHttpRequests;
use Illuminate\Http\Request;
use function Laravel\Folio\{middleware, name, render};

middleware([LogHttpRequests::class, 'auth', CheckPermissionsHttpRequest::class]);
name('collections');
render(function (Request $request) {
  return app(CollectionsController::class)($request);
});
?>

<x-layouts.app>
  <div class="ui:mx-auto ui:flex ui:w-full ui:max-w-7xl ui:flex-col ui:gap-6 ui:px-4 ui:py-8 ui:lg:px-8">

    <x-ui.page-header :title="__('Collections')"
                      :subtitle="__('Groups of documents used by CyberBuddy. Click a priority to edit it.')"/>

    <x-ui.card flush>
      @if($collections->isEmpty())
        <x-ui.empty icon="folders">{{ __('No collection.') }}</x-ui.empty>
      @else
        <x-ui.table>
          <thead>
          <tr>
            <th class="ui:text-right!">{{ __('Priority') }}</th>
            <th>{{ __('Name') }}</th>
            <th class="ui:text-right!">{{ __('Number of Documents') }}</th>
            <th class="ui:text-right!">{{ __('Number of Chunks') }}</th>
            <th class="ui:text-right!">{{ __('Number of Vectors') }}</th>
            <th>{{ __('Created At') }}</th>
            <th>{{ __('Created By') }}</th>
            <th></th>
          </tr>
          </thead>
          <tbody>
          @foreach($collections as $collection)
            <tr>
              {{-- Editable in place: editCollection() parses the cell text, keep it the bare number --}}
              <td class="ui:text-right ui:tabular-nums ui:cursor-text" title="{{ __('Click to edit') }}"
                  onclick="editCollection(this, {{ $collection->id }})">{{ $collection->priority }}</td>
              <td><x-ui.tag tone="auto">{{ $collection->name }}</x-ui.tag></td>
              <td class="ui:text-right ui:tabular-nums">
                <a href="{{ route('documents', ['page' => 1, 'collection' => $collection->name]) }}"
                   class="ui:text-brand-600! ui:hover:text-brand-700!">
                  {{ Illuminate\Support\Number::format($collection->files->count(), locale:'sv') }}
                </a>
              </td>
              <td class="ui:text-right ui:tabular-nums">
                <a href="{{ route('chunks', ['page' => 1, 'collection' => $collection->name]) }}"
                   class="ui:text-brand-600! ui:hover:text-brand-700!">
                  {{ Illuminate\Support\Number::format($collection->chunks->count(), locale:'sv') }}
                </a>
              </td>
              <td class="ui:text-right ui:tabular-nums">
                {{ Illuminate\Support\Number::format($collection->chunks->where('is_embedded', true)->count(), locale:'sv') }}
              </td>
              <td class="ui:whitespace-nowrap ui:text-slate-500">{{ $collection->created_at->format('Y-m-d H:i') }}</td>
              <td class="ui:text-slate-500">{{ $collection->createdBy?->name }}</td>
              <td>
                <div class="ui:flex ui:justify-end">
                  <x-ui.icon-button icon="trash" :title="__('Delete')" onclick="deleteCollection({{ $collection->id }})"/>
                </div>
              </td>
            </tr>
          @endforeach
          </tbody>
        </x-ui.table>
        <x-ui.pagination :page="$currentPage" :pages="$nbPages"
                         :url="fn($page) => route('collections', ['page' => $page])"
                         class="ui:border-0 ui:border-t ui:border-solid ui:border-line"/>
      @endif
    </x-ui.card>
  </div>
  <script>

    function deleteCollection(collectionId) {

      const response = confirm("{{ __('Are you sure you want to delete this collection?') }}");

      if (response) {
        deleteCollectionApiCall(collectionId);
      }
    }

    function editCollection(td, collectionId) {

      if (td.getAttribute('contenteditable') === 'true') {
        return; // Prevent multiple edits
      }

      const originalPriority = parseInt(td.innerText.trim(), 10);

      td.setAttribute('contenteditable', 'true');
      td.focus();
      td.onblur = () => {
        td.removeAttribute('contenteditable');
        td.innerText = td.innerText.trim();
        setPriority(td, collectionId, originalPriority, parseInt(td.innerText, 10));
      }
    }

    function setPriority(td, collectionId, oldValue, newValue) {
      if (oldValue !== newValue) {

        const response = confirm("{{ __('Are you sure you want to edit this collection?') }}");

        if (!response) {
          td.innerText = oldValue;
        } else {
          updateCollectionApiCall(collectionId, newValue);
        }
      }
    }

  </script>
</x-layouts.app>
