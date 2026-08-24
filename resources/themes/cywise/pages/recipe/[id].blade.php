<?php

use Livewire\Attributes\Computed;
use Livewire\Volt\Component;
use function Laravel\Folio\{middleware, name};

middleware('auth');
name('recipe');

new class extends Component {

  public $id;

  #[Computed]
  public function recipe(): string
  {
    $alert = \App\Models\Alert::find($this->id);
    if ($alert && $alert->asset()) {
      return (new Parsedown)->text($alert->remediationText() ?: __('There is no remediation information available for this alert.'));
    }
    return (new Parsedown)->text(__('There is no remediation information available for this alert.'));
  }

  #[Computed]
  public function script(): ?string
  {
    $alert = \App\Models\Alert::find($this->id);
    if ($alert && $alert->asset()) {
      return $alert->remediationScript();
    }
    return null;
  }

  public function downloadScript()
  {
    $script = $this->script;
    if (!$script) {
      return;
    }
    return response()->streamDownload(function () use ($script) {
      echo $script;
    }, 'remediation.sh');
  }
}

?>

<x-dynamic-component component="layouts.app" :title="__('Remediation recipe')">
  @volt('recipe')
  <div class="container-fluid">
    <div class="row">
      <div class="col">
        <div class="card mt-3">
          <div class="card-body p-4">
            <x-app.heading
                title="{!! __('Remediation recipe') !!}"
                description=""
            />
            <div class="mt-3">
              <div class="d-flex flex-column align-items-start gap-3 flex-lg-row gap-lg-4">
                <div>
                  @if($this->script)
                  <div class="mt-3">
                    <x-elements.button wire:click="downloadScript" class="btn btn-primary w-full">
                      {{ __('Download remediation script') }}
                    </x-elements.button>
                  </div>
                  <hr class="mt-6">
                  @endif
                  <div class="mt-2 text-muted">
                    {!! $this->recipe !!}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  @endvolt
</x-dynamic-component>

