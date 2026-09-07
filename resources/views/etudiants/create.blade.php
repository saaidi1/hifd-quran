@extends('layouts.app')

@section('title', __('Register new student'))
@section('page-title', __('Register new student'))

@section('content')
    @include('etudiants._form')
@endsection
