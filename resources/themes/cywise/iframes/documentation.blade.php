@extends('theme::iframes.app')

@section('content')
<div class="row pt-3">
  <div class="col">
    <div class="card">
      <div class="card-body">
        <h6 class="card-title">
          {{ __('API') }}
        </h6>
        <div class="card-text mb-3">
          La documentation de l'API est <a href="{{ route('v2.private.rpc.docs') }}"
                                           class="link"
                                           target="_blank">ici</a>.
        </div>
      </div>
    </div>
  </div>
  <div class="col">
    <div class="card">
      <div class="card-body">
        <h6 class="card-title">
          {{ __('Data Management') }}
        </h6>
        <div class="card-text mb-3">
          La documentation de l'interface utilisateur est accessible <a
            href="https://computablefacts.notion.site/Guide-utilisateur-2160a1f68ecc80689497e7dd5c07a817?source=copy_link"
            class="link"
            target="_blank">ici</a>.
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
