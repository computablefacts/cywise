@extends('theme::iframes.app')

@push('styles')
<style>

  .pre-light {
    color: #565656;
  }

</style>
@endpush

@section('content')
<div class="card mt-3 mb-3">
  @if($prompts->isEmpty())
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
        <th>{{ __('Name') }}</th>
        <th class="text-end">{{ __('Length') }}</th>
        <th>{{ __('Created At') }}</th>
        <th>{{ __('Created By') }}</th>
        <th></th>
      </tr>
      </thead>
      <tbody>
      @foreach($prompts as $prompt)
      <tr>
        <td>{{ $prompt->name }}</td>
        <td class="text-end">
          {{ Illuminate\Support\Number::format(\Illuminate\Support\Str::length($prompt->template), locale:'sv') }}
        </td>
        <td>{{ $prompt->created_at->format('Y-m-d H:i') }}</td>
        <td>{{ $prompt->createdBy()->name }}</td>
        <td class="text-end">
          <a href="#" onclick="deletePrompt({{ $prompt->id }})" class="text-decoration-none" style="color:red">
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
          <a data-bs-toggle="collapse" href="#prompt{{ $prompt->id }}" class="text-decoration-none">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
              <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
              <path d="M9 6l6 6l-6 6"/>
            </svg>
          </a>
        </td>
      </tr>
      <tr class="collapse" id="prompt{{ $prompt->id }}">
        <td colspan="5" style="background-color:#fff3cd;">
          <div style="display:grid;">
            <div class="overflow-auto">
              <pre class="mb-0 w-100 pre-light"
                   onclick="editPrompt(this, {{ $prompt->id }})">{{ $prompt->template }}</pre>
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
            <a class="page-link" href="{{ route('iframes.prompts', ['page' => 1]) }}">
              <span>&laquo;&nbsp;{{ __('First') }}</span>
            </a>
          </li>
          <li class="page-item {{ $currentPage <= 1 ? 'disabled' : '' }}">
            <a class="page-link"
               href="{{ route('iframes.prompts', ['page' => $currentPage <= 1 ? 1 : $currentPage - 1]) }}">
              <span>&lt;&nbsp;{{ __('Previous') }}</span>
            </a>
          </li>
          <li class="page-item">
            <a class="page-link active"
               href="{{ route('iframes.prompts', ['page' => $currentPage]) }}">
              {{ $currentPage }}
            </a>
          </li>
          <li class="page-item {{ $currentPage >= $nbPages ? 'disabled' : '' }}">
            <a class="page-link"
               href="{{ route('iframes.prompts', ['page' => $currentPage >= $nbPages ? $nbPages : $currentPage + 1])}}">
              <span>{{ __('Next') }}&nbsp;&gt;</span>
            </a>
          </li>
          <li class="page-item {{ $currentPage >= $nbPages ? 'disabled' : '' }}">
            <a class="page-link" href="{{ route('iframes.prompts', ['page' => $nbPages]) }}">
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

    pre.classList.toggle('pre-light');
    pre.setAttribute('contenteditable', 'true');
    pre.focus();
    pre.onblur = () => {
      pre.removeAttribute('contenteditable');
      pre.classList.toggle('pre-light');
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
@endpush
