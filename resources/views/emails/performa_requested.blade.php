@extends('emails.layouts.basic')

@section('title', $title ?? '')
@section('content')
<ul>
  <li><b>dns.</b> {{ $dns }}</li>
  <li><b>secret.</b> {{ $secret }}</li>
  <li><b>query.</b> UPDATE users SET performa_domain = '{{ $dns }}', performa_secret = '{{ $secret }}' WHERE id = {{ $id }};</li>
</ul>
@endsection
