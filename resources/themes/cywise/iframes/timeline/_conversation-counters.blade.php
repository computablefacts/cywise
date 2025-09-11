<div class="row mt-3 mb-3">
  <div class="col col-4">
    <div class="card">
      <div class="card-body p-3">
        <div class="row align-items-center">
          <div class="col-auto">
            <div class="d-flex align-content-center">
              <span class="bg-blue text-white avatar">
                <span class="bp4-icon bp4-icon-info-sign"></span>
              </span>
            </div>
          </div>
          <div class="col">
            <div class="h5 mb-0">
              <b>{{ $nb_conversations }}</b>
            </div>
            <div class="text-muted">
              <a href="{{ route('iframes.conversations') }}" class="link">
                {{ __('Conversations') }}
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>