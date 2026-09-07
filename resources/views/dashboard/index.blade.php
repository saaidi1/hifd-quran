@extends('layouts.app')

@section('title', __('Dashboard'))
@section('page-title', __('Dashboard'))

@section('content')
    @php
        $user = auth()->user();
    @endphp

    {{-- En-tête de bienvenue --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <h4 class="mb-0">{{ __('Greeting') }}{{ $user->nom_ar ?: $user->nom_complet }}</h4>
        <span class="text-muted"><i class="bi bi-calendar3 me-1"></i>{{ now()->format('d/m/Y') }}</span>
    </div>

    {{-- Cartes statistiques communes --}}
    @include('dashboard.partials.stat-cards')

    @if ($user->estDirecteur())
        @include('dashboard.partials.directeur')
    @elseif ($user->estSuperviseur())
        @include('dashboard.partials.superviseur')
    @elseif ($user->estGarde())
        @include('dashboard.partials.garde')
    @else
        @include('dashboard.partials.professeur')
    @endif
@endsection