@extends('theme::iframes.app')

@section('content')
<div class="card mt-3 mb-3">
  @if($collections->isEmpty())
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
        <th class="text-end">{{ __('Priority') }}</th>
        <th>{{ __('Name') }}</th>
        <th style="text-align:right">{{ __('Number of Documents') }}</th>
        <th style="text-align:right">{{ __('Number of Chunks') }}</th>
        <th style="text-align:right">{{ __('Number of Vectors') }}</th>
        <th>{{ __('Created At') }}</th>
        <th>{{ __('Created By') }}</th>
        <th></th>
      </tr>
      </thead>
      <tbody>
      @foreach($collections as $collection)
      <tr style="border-bottom-color:white">
        <td class="text-end" onclick="editCollection(this, {{ $collection->id }})">{{
          $collection->priority }}
        </td>
        <td><span class="lozenge new">{{ $collection->name }}</span></td>
        <td style="text-align:right">
          <a href="{{ route('iframes.documents', ['page' => 1, 'collection' => $collection->name]) }}">
            {{ Illuminate\Support\Number::format($collection->files->count(), locale:'sv') }}
          </a>
        </td>
        <td style="text-align:right">
          <a href="{{ route('iframes.chunks', ['page' => 1, 'collection' => $collection->name]) }}">
            {{ Illuminate\Support\Number::format($collection->chunks->count(), locale:'sv') }}
          </a>
        </td>
        <td style="text-align:right">
          {{ Illuminate\Support\Number::format($collection->chunks->where('is_embedded', true)->count(), locale:'sv') }}
        </td>
        <td>{{ $collection->created_at->format('Y-m-d H:i') }}</td>
        <td>{{ $collection->createdBy()?->name }}</td>
        <td class="text-end">
          <a href="#" onclick="deleteCollection({{ $collection->id }})" class="text-decoration-none" style="color:red">
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
            <a class="page-link" href="{{ route('collections', ['page' => 1]) }}">
              <span>&laquo;&nbsp;{{ __('First') }}</span>
            </a>
          </li>
          <li class="page-item {{ $currentPage <= 1 ? 'disabled' : '' }}">
            <a class="page-link"
               href="{{ route('collections', ['page' => $currentPage <= 1 ? 1 : $currentPage - 1]) }}">
              <span>&lt;&nbsp;{{ __('Previous') }}</span>
            </a>
          </li>
          <li class="page-item">
            <a class="page-link active"
               href="{{ route('collections', ['page' => $currentPage]) }}">
              {{ $currentPage }}
            </a>
          </li>
          <li class="page-item {{ $currentPage >= $nbPages ? 'disabled' : '' }}">
            <a class="page-link"
               href="{{ route('collections', ['page' => $currentPage >= $nbPages ? $nbPages : $currentPage + 1])}}">
              <span>{{ __('Next') }}&nbsp;&gt;</span>
            </a>
          </li>
          <li class="page-item {{ $currentPage >= $nbPages ? 'disabled' : '' }}">
            <a class="page-link" href="{{ route('collections', ['page' => $nbPages]) }}">
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
@endpush