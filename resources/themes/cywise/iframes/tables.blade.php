@extends('theme::iframes.app')

@section('content')
<h6 class="m-0 mt-3 mb-3">
  <a href="{{ route('iframes.table') }}">
    {{ __('+ new') }}
  </a>
</h6>
<div class="card mt-3 mb-3">
  <div class="card-body p-0">
    <x-tables-list/>
  </div>
</div>
@endsection
