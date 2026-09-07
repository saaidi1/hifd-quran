@extends('layouts.app')

@section('title', __('Edit student'))
@section('page-title', __('Edit student') . ': ' . $etudiant->nom_complet_ar)

@section('content')
    @include('etudiants._form', ['etudiant' => $etudiant])
@endsection
