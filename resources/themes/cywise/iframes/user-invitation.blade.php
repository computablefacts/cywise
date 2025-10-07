@extends('theme::iframes.app')

@section('content')
  <div class="card mt-3 mb-3">
    <div class="card-body">
      <h6 class="card-title text-truncate">{{ __('Send an invitation') }}</h6>
      <div class="card mb-3" style="background-color:#fff3cd;">
        <div class="card-body p-2">
          <div class="row">
            <div class="col">
              {!! __('Enter the email address of the person invited to join your team below.') !!}
            </div>
          </div>
        </div>
      </div>
      <div class="form-group">
        <div class="mb-3">
          <label for="email" class="form-label">{{ __('Email') }}</label>
          <input id="email" class="form-control">
        </div>
        <div class="mb-3">
          <div class="col text-center">
            <button id="create-invitation" class="btn btn-primary">
              {{ __('Save') }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection

@push('scripts')
  <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.6.0/ace.js"></script>
  <script>

    const btnCreate = document.querySelector('#create-invitation');
    const elEmail = document.querySelector('#email');

    btnCreate.addEventListener('click', () => {
      createUserInvitationApiCall(elEmail.value,
          () => toaster.toastSuccess("{{ __('The invitation has been sent.') }}"));
    });

  </script>
@endpush