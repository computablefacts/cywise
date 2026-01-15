@extends('emails.layouts.basic')

@section('title', $title ?? '')
@section('content')
<ul>
    <li><b>provider.</b> {{ $cloud_provider }}</li>
    <li><b>sensor.</b> {{ $cloud_sensor }}</li>
    <li><b>dns.</b> {{ $dns }}</li>
    <li><b>query.</b> UPDATE am_honeypots SET status = 'setup_complete' WHERE id = {{ $id }};</li>
</ul>
@endsection
