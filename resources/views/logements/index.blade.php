@extends('layouts.app')

@section('title', __('Rooms and accommodation'))
@section('page-title', __('Rooms and accommodation'))

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <p class="text-muted mb-0">{{ __('Boarding rooms and their beds.') }}</p>
        <a href="{{ route('logements.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>{{ __('New room') }}
        </a>
    </div>

    <div class="row g-3">
        @forelse ($chambres as $c)
            @php
                $libres = $c->litsLibres();
                $pensionnaires = $c->hebergements;
            @endphp
            <div class="col-md-6 col-xl-4">
                <div class="app-card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center px-3 py-2">
                        <span class="fw-bold">{{ __('Room :number', ['number' => $c->numero]) }}
                            @if ($c->batiment) <span class="text-muted small fw-normal">— {{ $c->batiment }} {{ $c->etage ?? '' }}</span> @endif
                        </span>
                        <span class="badge text-bg-{{ $c->actif ? 'success' : 'secondary' }}">{{ $c->actif ? __('Available') : __('Closed') }}</span>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between small text-muted mb-2">
                            <span><i class="bi bi-person-bounding-box me-1"></i>{{ __(':count beds', ['count' => $c->capacite]) }}</span>
                            <span class="badge text-bg-{{ count($libres) ? 'success' : 'danger' }}">
                                {{ count($libres) ? implode('، ', $libres) . ' ' . __('free') : __('Full') }}
                            </span>
                        </div>
                        <div class="progress mb-2" style="height:6px;">
                            <div class="progress-bar bg-{{ count($libres) ? 'primary' : 'danger' }}" style="width: {{ $c->capacite ? min(100, $pensionnaires->count() / $c->capacite * 100) : 0 }}%"></div>
                        </div>
                        <ul class="list-unstyled small mb-0">
                            @forelse ($pensionnaires as $h)
                                <li class="d-flex justify-content-between border-bottom py-1">
                                    <span>{{ __('Bed :number', ['number' => $h->numero_lit]) }} : {{ $h->etudiant->nom_complet_ar }}</span>
                                </li>
                            @empty
                                <li class="text-muted">{{ __('No residents.') }}</li>
                            @endforelse
                        </ul>
                    </div>
                    <div class="card-footer bg-transparent d-flex justify-content-end gap-1">
                        <a href="{{ route('logements.edit', $c) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                        <form method="POST" action="{{ route('logements.destroy', $c) }}" onsubmit="return confirm('{{ __('Confirm deletion?') }}');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger" {{ $c->hebergements()->exists() ? 'disabled' : '' }}><i class="bi bi-trash"></i></button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="app-card p-5 text-center text-muted">{{ __('No rooms yet.') }}</div>
            </div>
        @endforelse
    </div>
@endsection
