<div class="row mt-3 mb-3">
  <div class="col">
    <div class="card">
      <div class="card-body p-3">
        <div class="row align-items-center">
          <div class="col-auto">
            <div class="d-flex align-content-center">
              <span class="bg-red text-white avatar">
                <span class="bp4-icon bp4-icon-issue"></span>
              </span>
            </div>
          </div>
          <div class="col">
            <div class="h5 mb-0">
              <b>{{ $nb_high }}</b>
            </div>
            <div class="text-muted">
              <a href="{{ route('iframes.ioc', [ 'level' => 'high' ]) }}" class="link">
                {{ __('High') }}
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col ps-0">
    <div class="card">
      <div class="card-body p-3">
        <div class="row align-items-center">
          <div class="col-auto">
            <div class="d-flex align-content-center">
              <span class="bg-orange text-white avatar">
                <span class="bp4-icon bp4-icon-issue"></span>
              </span>
            </div>
          </div>
          <div class="col">
            <div class="h5 mb-0">
              <b>{{ $nb_medium }}</b>
            </div>
            <div class="text-muted">
              <a href="{{ route('iframes.ioc', [ 'level' => 'medium' ]) }}" class="link">
                {{ __('Medium') }}
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col ps-0">
    <div class="card">
      <div class="card-body p-3">
        <div class="row align-items-center">
          <div class="col-auto">
            <div class="d-flex align-content-center">
              <span class="bg-green text-white avatar">
                <span class="bp4-icon bp4-icon-issue"></span>
              </span>
            </div>
          </div>
          <div class="col">
            <div class="h5 mb-0">
              <b>{{ $nb_low }}</b>
            </div>
            <div class="text-muted">
              <a href="{{ route('iframes.ioc', [ 'level' => 'low' ]) }}" class="link">
                {{ __('Low') }}
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col ps-0">
    <div class="card">
      <div class="card-body p-3">
        <div class="row align-items-center">
          <div class="col-auto">
            <div class="d-flex align-content-center">
              <span class="text-white avatar" style="background-color: var(--c-grey-100);">
                <span class="bp4-icon bp4-icon-issue text-black"></span>
              </span>
            </div>
          </div>
          <div class="col">
            <div class="h5 mb-0">
              <b>{{ $nb_suspect }}</b>
            </div>
            <div class="text-muted">
              <a href="{{ route('iframes.ioc', [ 'level' => 'suspect' ]) }}" class="link">
                {{ __('Suspect') }}
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>