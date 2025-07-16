@extends('theme::iframes.app')

@section('content')
<div class="container-fluid p-0 pt-3 pb-3">
  <div class="row justify-content-center">
    <div class="col">
      <div class="card">
        <div class="card-body">
          {!! $html !!}
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
