<?php

use App\Http\Controllers\Iframes\DocumentsController;
use App\Http\Middleware\CheckPermissionsHttpRequest;
use App\Http\Middleware\LogHttpRequests;
use Illuminate\Http\Request;
use function Laravel\Folio\{middleware, name, render};

middleware([LogHttpRequests::class, 'auth', CheckPermissionsHttpRequest::class]);
name('documents');
render(function (Request $request) {
  return app(DocumentsController::class)($request);
});
?>

<x-layouts.app>
  <div class="ui:mx-auto ui:flex ui:w-full ui:max-w-7xl ui:flex-col ui:gap-6 ui:px-4 ui:py-8 ui:lg:px-8">

    <x-ui.page-header :title="__('Documents')"
                      :subtitle="__('Files imported into your collections and searchable by CyberBuddy.')"/>

    {{-- Upload: BlueprintJS widgets mounted on #collections, #files and #submit by the script below --}}
    <x-ui.card :title="__('Import your documents !')"
               :subtitle="__('Authorized file formats: PDF, DOC, DOCX, TXT, JSON, JSONL, MP3, WAV, and WEBM.')">
      <div class="ui:grid ui:items-end ui:gap-3 ui:lg:grid-cols-[1fr_2fr_auto]">
        <x-ui.field :label="__('Collection')">
          <div id="collections"></div>
        </x-ui.field>
        <x-ui.field :label="__('Files')">
          <div id="files"></div>
        </x-ui.field>
        <div id="submit"></div>
      </div>
    </x-ui.card>

    {{-- Filtered by collection (link from the collections page) --}}
    @if($collection)
      <div class="ui:flex ui:flex-wrap ui:items-center ui:justify-between ui:gap-3 ui:rounded-xl ui:border ui:border-solid ui:border-blue-200 ui:bg-info-soft ui:px-4 ui:py-3 ui:text-sm ui:text-blue-900">
        <span class="ui:flex ui:items-center ui:gap-2">
          <x-phosphor-funnel class="ui:size-4 ui:shrink-0"/>
          {{ __('Only the documents from the ":collection" collection are displayed.', ['collection' => $collection]) }}
        </span>
        <x-ui.button variant="ghost" size="sm" :href="route('documents')">{{ __('Reset filters') }}</x-ui.button>
      </div>
    @endif

    <x-ui.card flush>
      @if($files->isEmpty())
        <x-ui.empty icon="files">{{ __('No document.') }}</x-ui.empty>
      @else
        <x-ui.table>
          <thead>
          <tr>
            <th>{{ __('Collection') }}</th>
            <th>{{ __('Filename') }}</th>
            <th class="ui:text-right!">{{ __('File Size') }}</th>
            <th class="ui:text-right!">{{ __('Number of Chunks') }}</th>
            <th class="ui:text-right!">{{ __('Number of Vectors') }}</th>
            <th>{{ __('Imported At') }}</th>
            <th>{{ __('Imported By') }}</th>
            <th class="ui:text-right!">{{ __('Integration Status') }}</th>
            <th></th>
          </tr>
          </thead>
          <tbody>
          @foreach($files as $file)
            <tr>
              <td><x-ui.tag tone="auto">{{ $file['collection'] }}</x-ui.tag></td>
              <td>
                <a href="{{ $file['download_url'] }}" class="ui:font-medium ui:text-ink! ui:hover:text-brand-600!">
                  {{ $file['filename'] }}
                </a>
              </td>
              <td class="ui:text-right ui:tabular-nums">
                {{ Illuminate\Support\Number::format($file['size'], locale:'sv') }}
              </td>
              <td class="ui:text-right ui:tabular-nums">
                <a href="{{ route('chunks', ['page' => 1, 'collection' => $file['collection'], 'file' => $file['name_normalized']]) }}"
                   class="ui:text-brand-600! ui:hover:text-brand-700!">
                  {{ Illuminate\Support\Number::format($file['nb_chunks'], locale:'sv') }}
                </a>
              </td>
              <td class="ui:text-right ui:tabular-nums">
                {{ Illuminate\Support\Number::format($file['nb_vectors'], locale:'sv') }}
              </td>
              <td class="ui:whitespace-nowrap ui:text-slate-500">{{ $file['created_at']->format('Y-m-d H:i') }}</td>
              <td class="ui:text-slate-500">{{ $file['created_by']->name }}</td>
              <td class="ui:text-right">
                <x-ui.badge :level="$file['status'] === 'processed' ? 'low' : 'info'">{{ __($file['status']) }}</x-ui.badge>
              </td>
              <td>
                <div class="ui:flex ui:justify-end">
                  <x-ui.icon-button icon="trash" :title="__('Delete')" onclick="deleteFile({{ $file['id'] }})"/>
                </div>
              </td>
            </tr>
          @endforeach
          </tbody>
        </x-ui.table>
        <x-ui.pagination :page="$currentPage" :pages="$nbPages"
                         :url="fn($page) => route('documents', ['page' => $page, 'collection' => $collection])"
                         class="ui:border-0 ui:border-t ui:border-solid ui:border-line"/>
      @endif
    </x-ui.card>
  </div>
  <script>

    let files = null;
    let collection = null;

    const elSubmit = new com.computablefacts.blueprintjs.MinimalButton(document.getElementById('submit'),
      "{{ __('Submit') }}");
    elSubmit.disabled = true;
    elSubmit.onClick(() => {

      elSubmit.loading = true;
      elSubmit.disabled = true;

      const formData = new FormData();
      formData.append('collection', collection);

      for (let i = 0; i < files.length; i++) {
        formData.append('files[]', files[i]);
      }

      axios.post('/files/many', formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
        }
      }).then(response => {
        window.toaster.toastSuccess("{{ __('Your file has been successfully uploaded. It will be available shortly.') }}");
      }).catch(error => {
        window.toaster.toastAxiosError(error);
      }).finally(() => {
        elSubmit.loading = false;
        elSubmit.disabled = false;
      });
    });

    const elFile = new com.computablefacts.blueprintjs.MinimalFileInput(document.getElementById('files'), true);
    elFile.onSelectionChange(items => {
      files = items;
      elSubmit.disabled = !files || !collection;
    });
    elFile.buttonText = "{{ __('Browse') }}";

    const elCollections = new com.computablefacts.blueprintjs.MinimalSelect(document.getElementById('collections'), null,
      null, null, query => query);
    elCollections.onSelectionChange(item => {
      collection = item;
      elSubmit.disabled = !files || !collection;
    });
    elCollections.defaultText = "{{ __('Select or create collection...') }}";

    document.addEventListener('DOMContentLoaded', function (event) {
      listCollectionsApiCall(response => {
        elCollections.items = response.collections.map(collection => collection.name);
        if ("{{ $collection }}" !== "") {
          elCollections.selectedItem = "{{ $collection }}";
          elCollections.disabled = true;
          collection = elCollections.selectedItem;
        }
      });
    });

    function deleteFile(fileId) {

      const response = confirm("{{ __('Are you sure you want to delete this file?') }}");

      if (response) {
        deleteFileApiCall(fileId);
      }
    }

  </script>
</x-layouts.app>
