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
        <pre class="mb-0">
curl -s "{{ app_url() }}/setup/script?api_token={{ Auth::user()->sentinelApiToken() }}&server_ip=$(curl -s ipinfo.io | jq -r '.ip')&server_name=$(hostname)" | bash
        </pre>
      </div>
      <div class="tab-pane" id="tab-windows" role="tabpanel" aria-labelledby="tab-windows">
        {{ __('To monitor a new Windows server, log in as administrator and execute this command line:') }}
        <br><br>
        <pre class="mb-0">
Invoke-WebRequest -Uri "{{ app_url() }}/setup/script?api_token={{ Auth::user()->sentinelApiToken() }}&server_ip=$((Invoke-RestMethod -Uri 'https://ipinfo.io').ip)&server_name=$($env:COMPUTERNAME)&platform=windows" -UseBasicParsing | Invoke-Expression
        </pre>
      </div>
    </div>
  </div>
</div>