<div class="card mt-3">
  <div class="card-body">
    <h6 class="card-title text-truncate">
      {{ __('Would you like to protect a new server?') }}
    </h6>
    <ul class="nav nav-tabs" role="tablist">
      <li class="nav-item" role="presentation">
        <a class="nav-link active" data-bs-toggle="tab" href="#tab-linux" role="tab"
           aria-controls="tab-linux" aria-selected="true">
          {{ __('Linux') }}
        </a>
      </li>
      <li class="nav-item" role="presentation">
        <a class="nav-link" data-bs-toggle="tab" href="#tab-windows" role="tab"
           aria-controls="tab-windows" aria-selected="false">
          {{ __('Windows') }}
        </a>
      </li>
      <li class="nav-item" role="presentation">
        <a class="nav-link disabled" data-bs-toggle="tab" href="#tab-macos" role="tab"
           aria-controls="tab-macos" aria-selected="false">
          {{ __('MacOS') }}
        </a>
      </li>
    </ul>
    <div class="tab-content pt-5" id="tab-content">
      <div class="tab-pane active" id="tab-linux" role="tabpanel" aria-labelledby="tab-linux">
        {{ __('To monitor a new Linux server, log in as root and execute this command line:') }}
        <br><br>
        <div class="input-group">
          <input type="text" id="linux-install-cmd" class="form-control" readonly
                 value="curl -s &quot;{{ app_url() }}/setup/script?api_token={{ Auth::user()->sentinelApiToken() }}&amp;server_ip=$(curl -s ipinfo.io | jq -r '.ip')&amp;server_name=$(hostname)&quot; | bash">
          <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('linux-install-cmd')">
            {{ __('Copy') }}
          </button>
        </div>
      </div>
      <div class="tab-pane" id="tab-windows" role="tabpanel" aria-labelledby="tab-windows">
        {{ __('To monitor a new Windows server, log in as administrator and execute this command line:') }}
        <br><br>
        <div class="input-group">
          <input type="text" id="windows-install-cmd" class="form-control" readonly
                 value="Invoke-WebRequest -Uri &quot;{{ app_url() }}/setup/script?api_token={{ Auth::user()->sentinelApiToken() }}&amp;server_ip=$((Invoke-RestMethod -Uri 'https://ipinfo.io').ip)&amp;server_name=$($env:COMPUTERNAME)&amp;platform=windows&quot; -UseBasicParsing | Invoke-Expression">
          <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('windows-install-cmd')">
            {{ __('Copy') }}
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
  if (typeof copyToClipboard !== 'function') {
    function copyToClipboard(id) {
      const el = document.getElementById(id);
      el.select();
      document.execCommand('copy');
      if (typeof toaster !== 'undefined') {
        toaster.toastSuccess("{{ __('Copied to clipboard') }}");
      }
    }
  }
</script>
@endpush