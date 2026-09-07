@extends('layouts.app')

@section('title', __('Halaqas'))
@section('page-title', __('Halaqas'))

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <p class="text-muted mb-0">{{ __('Memorization halaqas and their teachers.') }}</p>
        @if (auth()->user()->aRole(\App\Enums\RoleUtilisateur::DIRECTEUR, \App\Enums\RoleUtilisateur::PROFESSEUR, \App\Enums\RoleUtilisateur::SUPERVISEUR, \App\Enums\RoleUtilisateur::GARDE))
            <a href="{{ route('groupes.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i>{{ __('New halaqa') }}
            </a>
        @endif
    </div>

    @php
        $niveaux = ['mubtadi' => __('Beginner'), 'moutawassit' => __('Intermediate'), 'moutaqaddim' => __('Advanced'), 'khatma' => __('Complete recitation')];
        $peutGerer = auth()->user()->aRole(\App\Enums\RoleUtilisateur::DIRECTEUR, \App\Enums\RoleUtilisateur::PROFESSEUR, \App\Enums\RoleUtilisateur::SUPERVISEUR, \App\Enums\RoleUtilisateur::GARDE);
    @endphp

    <div class="row g-3">
        @forelse ($groupes as $g)
            <div class="col-md-6 col-xl-4">
                <div class="app-card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center px-3 py-2">
                        <span class="fw-bold">{{ $g->nom_ar }}</span>
                        <span class="badge text-bg-{{ $g->actif ? 'success' : 'secondary' }}">{{ $g->actif ? __('Active') : __('Stopped') }}</span>
                    </div>
                    <div class="card-body">
                        <div class="small text-muted mb-2">
                            <i class="bi bi-person-workspace me-1"></i>{{ $g->professeur?->nom_ar ?? __('Unspecified') }}
                        </div>
                        <div class="d-flex gap-2 flex-wrap small mb-2">
                            @if ($g->niveau)
                                <span class="badge text-bg-light">{{ $niveaux[$g->niveau] ?? $g->niveau }}</span>
                            @endif
                            @if ($g->salle)<span class="badge text-bg-light"><i class="bi bi-door-open me-1"></i>{{ $g->salle }}</span>@endif
                            @if ($g->horaire_debut || $g->horaire_fin)
                                <span class="badge text-bg-light">
                                    <i class="bi bi-clock me-1"></i>
                                    @if ($g->horaire_debut && $g->horaire_fin)
                                        {{ __('From :from to :to', ['from' => $g->horaire_debut, 'to' => $g->horaire_fin]) }}
                                    @elseif ($g->horaire_debut)
                                        {{ __('From :from', ['from' => $g->horaire_debut]) }}
                                    @else
                                        {{ __('To :to', ['to' => $g->horaire_fin]) }}
                                    @endif
                                </span>
                            @endif
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small">
                                {{ __(':count students / :capacity', ['count' => $g->etudiants_count, 'capacity' => $g->capacite]) }}
                            </span>
                            @php $restantes = $g->placesRestantes(); @endphp
                            <span class="badge text-bg-{{ $restantes > 0 ? 'success' : 'danger' }}">{{ __(':count places available', ['count' => $restantes]) }}</span>
                        </div>
                        <div class="progress mt-2" style="height:6px;">
                            <div class="progress-bar bg-primary" style="width: {{ $g->capacite ? min(100, $g->etudiants_count / $g->capacite * 100) : 0 }}%"></div>
                        </div>
                    </div>
                    @if ($peutGerer && (auth()->user()->estDirecteur() || auth()->user()->estSuperviseur() || auth()->user()->estGarde() || $g->professeur_id === auth()->id()))
                        <div class="card-footer bg-transparent d-flex justify-content-end gap-1">
                            <a href="{{ route('groupes.edit', $g) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                            <form method="POST" action="{{ route('groupes.destroy', $g) }}"
                                  onsubmit="return confirm('{{ __('Confirm deletion of this halaqa?') }}');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" {{ $g->etudiants()->exists() ? 'disabled' : '' }}><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="app-card p-5 text-center text-muted">{{ __('No halaqas yet.') }}</div>
            </div>
        @endforelse
    </div>
@endsection
