@extends('theme::iframes.app')

@section('content')
<div class="card mt-3 mb-3">
  <div class="card-body p-3">
    <h6 class="card-title">{{ __('Import your documents !') }}</h6>
    <div class="card mb-3" style="background-color:#fff3cd;">
      <div class="card-body p-2">
        <div class="row">
          <div class="col">
            {{ __('Authorized file formats: PDF, DOC, DOCX, TXT, JSON, JSONL, MP3, WAV, and WEBM.') }}
          </div>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-3">
        <div id="collections"></div>
      </div>
      <div class="col">
        <div id="files"></div>
      </div>
      <div class="col-3">
        <div id="submit"></div>
      </div>
    </div>
  </div>
</div>
@if($collection)
<div class="card mt-3" style="border-top:1px solid #becdcf;background-color:#fff3cd;">
  <div class="card-body">
    <div class="row">
      <div class="col">
        {{ __('Only the documents from the ":collection" collection are displayed.', ['collection' => $collection]) }}
      </div>
    </div>
  </div>
</div>
@endif
<div class="card mt-3">
  @if($files->isEmpty())
  <div class="card-body">
    <div class="row">
      <div class="col">
        {{ __('None.') }}
      </div>
    </div>
  </div>
  @else
  <div class="card-body p-0">
    <table class="table table-hover no-bottom-margin">
      <thead>
      <tr>
        <th>{{ __('Collection') }}</th>
        <th>{{ __('Filename') }}</th>
        <th style="text-align:right">{{ __('File Size') }}</th>
        <th style="text-align:right">{{ __('Number of Chunks') }}</th>
        <th style="text-align:right">{{ __('Number of Vectors') }}</th>
        <th>{{ __('Imported At') }}</th>
        <th>{{ __('Imported By') }}</th>
        <th style="text-align:right">{{ __('Integration Status') }}</th>
        <th></th>
      </tr>
      </thead>
      <tbody>
      @foreach($files as $file)
      <tr>
        <td><span class="lozenge new">{{ $file['collection'] }}</span></td>
        <td>
          <a href="{{ $file['download_url'] }}">
            {{ $file['filename'] }}
          </a>
        </td>
        <td style="text-align:right">
          {{ Illuminate\Support\Number::format($file['size'], locale:'sv') }}
        </td>
        <td style="text-align:right">
          <a
            href="{{ route('iframes.chunks', ['page' => 1, 'collection' => $file['collection'], 'file' => $file['name_normalized']]) }}">
            {{ Illuminate\Support\Number::format($file['nb_chunks'], locale:'sv') }}
          </a>
        </td>
        <td style="text-align:right">
          {{ Illuminate\Support\Number::format($file['nb_vectors'], locale:'sv') }}
        </td>
        <td>{{ $file['created_at']->format('Y-m-d H:i') }}</td>
        <td>{{ $file['created_by']->name }}</td>
        <td style="text-align:right">
          @if($file['status'] === 'processed')
          <span class="lozenge success">{{ __($file['status']) }}</span>
          @else
          <span class="lozenge information">{{ __($file['status']) }}</span>
          @endif
        </td>
        <td class="text-end">
          <a href="#" onclick="deleteFile({{ $file['id'] }})" class="text-decoration-none" style="color:red">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
              <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
              <path d="M4 7l16 0"/>
              <path d="M10 11l0 6"/>
              <path d="M14 11l0 6"/>
              <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"/>
              <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"/>
            </svg>
          </a>
        </td>
      </tr>
      @endforeach
      </tbody>
    </table>
    <div class="row">
      <div class="col">
        <ul class="pagination justify-content-center mt-3 mb-3">
          <li class="page-item {{ $currentPage <= 1 ? 'disabled' : '' }}">
            <a class="page-link"
               href="{{ route('iframes.documents', ['page' => 1, 'collection' => $collection]) }}">
              <span>&laquo;&nbsp;{{ __('First') }}</span>
            </a>
          </li>
          <li class="page-item {{ $currentPage <= 1 ? 'disabled' : '' }}">
            <a class="page-link"
               href="{{ route('iframes.documents', ['page' => $currentPage <= 1 ? 1 : $currentPage - 1, 'collection' => $collection]) }}">
              <span>&lt;&nbsp;{{ __('Previous') }}</span>
            </a>
          </li>
          <li class="page-item">
            <a class="page-link active"
               href="{{ route('iframes.documents', ['page' => $currentPage, 'collection' => $collection]) }}">
              {{ $currentPage }}
            </a>
          </li>
          <li class="page-item {{ $currentPage >= $nbPages ? 'disabled' : '' }}">
            <a class="page-link"
               href="{{ route('iframes.documents', ['page' => $currentPage >= $nbPages ? $nbPages : $currentPage + 1, 'collection' => $collection])}}">
              <span>{{ __('Next') }}&nbsp;&gt;</span>
            </a>
          </li>
          <li class="page-item {{ $currentPage >= $nbPages ? 'disabled' : '' }}">
            <a class="page-link" href="{{ route('iframes.documents', ['page' => $nbPages]) }}">
              <span>{{ __('Last') }}&nbsp;&raquo;</span>
            </a>
          </li>
        </ul>
      </div>
    </div>
  </div>
  @endif
</div>
@endsection

@push('scripts')
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
      toaster.toastSuccess("{{ __('Your file has been successfully uploaded. It will be available shortly.') }}");
    }).catch(error => toaster.toastAxiosError(error)).finally(() => {
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
@endpush