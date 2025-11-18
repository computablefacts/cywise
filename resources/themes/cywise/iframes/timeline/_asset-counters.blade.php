<div class="row mt-3 mb-3">
  <div class="col">
    <div class="card">
      <div class="card-body p-3">
        <div class="row align-items-center">
          <div class="col-auto">
            <div class="d-flex align-content-center">
              <span class="bg-blue text-white avatar">
                <span class="bp4-icon bp4-icon-globe-network"></span>
              </span>
            </div>
          </div>
          <div class="col">
            <div class="h5 mb-0">
              <b>{{ $nb_monitored + $nb_monitorable }}</b>
            </div>
            <div class="text-muted">
              <a href="{{ route('iframes.assets', request()->only(['tld','tags'])) }}" class="link">
                {{ __('Assets') }}
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
              <span class="bg-blue text-white avatar">
                <span class="bp4-icon bp4-icon-globe-network"></span>
              </span>
            </div>
          </div>
          <div class="col">
            <div class="h5 mb-0">
              <b>{{ $nb_monitored }}</b>
            </div>
            <div class="text-muted">
              <a
                href="{{ route('iframes.assets', array_merge(['status' => 'monitored'], request()->only(['tld','tags']))) }}"
                class="link">
                {{ __('Assets Monitored') }}
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
              <span class="bg-blue text-white avatar">
                <span class="bp4-icon bp4-icon-globe-network"></span>
              </span>
            </div>
          </div>
          <div class="col">
            <div class="h5 mb-0">
              <b>{{ $nb_monitorable }}</b>
            </div>
            <div class="text-muted">
              <a
                href="{{ route('iframes.assets', array_merge(['status' => 'monitorable'], request()->only(['tld','tags']))) }}"
                class="link">
                {{ __('Assets Monitorable') }}
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>