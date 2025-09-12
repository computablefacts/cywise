@extends('theme::iframes.app')

@push('styles')
<style>

  .pre-light {
    color: #565656;
  }

</style>
@endpush

@section('content')
@if($file)
<div class="card mt-3" style="border-top:1px solid #becdcf;background-color:#fff3cd;">
  <div class="card-body">
    <div class="row">
      <div class="col">
        {{ __('Only the chunks from the ":document" document are displayed.', ['document' => $file]) }}
      </div>
    </div>
  </div>
</div>
@elseif($collection)
<div class="card mt-3" style="border-top:1px solid #becdcf;background-color:#fff3cd;">
  <div class="card-body">
    <div class="row">
      <div class="col">
        {{ __('Only the chunks from the ":collection" collection are displayed.', ['collection' => $collection]) }}
      </div>
    </div>
  </div>
</div>
@endif
<div class="card mt-3 mb-3">
  @if($chunks->isEmpty())
  <div class="card-body">
    <div class="row">
      <div class="col">
        {{ __('None.') }}
      </div>
    </div>
  </div>
  @else
  <div class="card-body p-0">
    <table class="table no-bottom-margin">
      <thead>
      <tr>
        <th></th>
        <th>{{ __('Collection') }}</th>
        <th>{{ __('Filename') }}</th>
        <th class="text-end">{{ __('Page') }}</th>
        <th class="text-end">{{ __('Length') }}</th>
        <th class="text-end">{{ __('Number of Vectors') }}</th>
        <th>{{ __('Created At') }}</th>
        <th></th>
      </tr>
      </thead>
      <tbody>
      @foreach($chunks as $chunk)
      <tr style="border-bottom-color:white">
        <td class="ps-4" width="25px">
          <span class="{{ $chunk->isEmbedded() ? 'dot-green' : 'dot-red' }}"></span>
        </td>
        <td>
          <span class="lozenge new">{{ $chunk->collection->name }}</span>
        </td>
        <td>
          <a href="{{ $chunk->file->downloadUrl() }}">
            {{ $chunk->file->name_normalized }}.{{ $chunk->file->extension }}
          </a>
        </td>
        <td class="ps-4 text-end" width="25px">
          <a href="{{ $chunk->file->downloadUrl() }}?page={{ $chunk->page }}">
            {{ $chunk->page }}
          </a>
        </td>
        <td class="text-end">
          {{ Illuminate\Support\Number::format(\Illuminate\Support\Str::length($chunk->text), locale:'sv') }}
        </td>
        <td class="text-end">
          {{ Illuminate\Support\Number::format($chunk->vectors()->count(), locale:'sv') }}
        </td>
        <td>{{ $chunk->created_at->format('Y-m-d H:i') }}</td>
        <td class="text-end">
          <a href="#" onclick="deleteChunk({{ $chunk->id }})" class="text-decoration-none" style="color:red">
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
          &nbsp;&nbsp;&nbsp;&nbsp;
          <a data-bs-toggle="collapse" href="#chunk{{ $chunk->id }}" class="text-decoration-none">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
              <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
              <path d="M9 6l6 6l-6 6"/>
            </svg>
          </a>
        </td>
      </tr>
      <tr>
        <td colspan="8">
          @foreach($chunk->tags()->orderBy('id')->get() as $tag)
          <span class="lozenge information">{{ $tag->tag }}</span>&nbsp;
          @endforeach
        </td>
      </tr>
      <tr class="collapse" id="chunk{{ $chunk->id }}">
        <td colspan="8" style="background-color:#fff3cd;">
          <div style="display:grid;">
            <div class="overflow-auto">
              <pre class="mb-0 w-100 pre-light" onclick="editChunk(this, {{ $chunk->id }})">{{ $chunk->text }}</pre>
            </div>
          </div>
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
               href="{{ route('iframes.chunks', ['page' => 1, 'collection' => $collection]) }}">
              <span>&laquo;&nbsp;{{ __('First') }}</span>
            </a>
          </li>
          <li class="page-item {{ $currentPage <= 1 ? 'disabled' : '' }}">
            <a class="page-link"
               href="{{ route('iframes.chunks', ['page' => $currentPage <= 1 ? 1 : $currentPage - 1, 'collection' => $collection]) }}">
              <span>&lt;&nbsp;{{ __('Previous') }}</span>
            </a>
          </li>
          <li class="page-item">
            <a class="page-link active"
               href="{{ route('iframes.chunks', ['page' => $currentPage, 'collection' => $collection]) }}">
              {{ $currentPage }}
            </a>
          </li>
          <li class="page-item {{ $currentPage >= $nbPages ? 'disabled' : '' }}">
            <a class="page-link"
               href="{{ route('iframes.chunks', ['page' => $currentPage >= $nbPages ? $nbPages : $currentPage + 1, 'collection' => $collection])}}">
              <span>{{ __('Next') }}&nbsp;&gt;</span>
            </a>
          </li>
          <li class="page-item {{ $currentPage >= $nbPages ? 'disabled' : '' }}">
            <a class="page-link"
               href="{{ route('iframes.chunks', ['page' => $nbPages, 'collection' => $collection]) }}">
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

    pre.classList.toggle('pre-light');
    pre.setAttribute('contenteditable', 'true');
    pre.focus();
    pre.onblur = () => {
      pre.removeAttribute('contenteditable');
      pre.classList.toggle('pre-light');
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
@endpush
