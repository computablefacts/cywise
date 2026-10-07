@extends('theme::iframes.app')

@section('content')
<div class="ui:mx-auto ui:flex ui:w-full ui:max-w-4xl ui:flex-col ui:gap-6 ui:px-4 ui:py-8 ui:lg:px-8">
  <x-ui.card>
    {!! $html !!}
  </x-ui.card>
</div>
@endsection
