@extends('emails.layouts.basic')

@section('title', $title ?? '')
@section('content')
{!! $content !!}
@endsection
