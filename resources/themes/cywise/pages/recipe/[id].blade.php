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
        return (new Parsedown)->text($alert->ai_remediation ?? __('There is no remediation information available for this alert.'));
    }
    return (new Parsedown)->text(__('There is no remediation information available for this alert.'));
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

