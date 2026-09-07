@extends('layouts.app')

@section('title', __('Periodic report'))
@section('page-title', __('Periodic report'))

@section('content')
    <div class="d-flex justify-content-between mb-3">
        <a href="{{ route('rapports-periodiques.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-right me-1"></i>{{ __('Back') }}</a>
        <div class="d-flex gap-1">
            <a href="{{ route('rapports-periodiques.pdf', $rapport) }}" class="btn btn-outline-danger"><i class="bi bi-filetype-pdf me-1"></i>{{ __('Download PDF') }}</a>
            @php
                $typeColors = ['hebdomadaire' => 'info', 'mensuel' => 'dark', 'annuel' => 'primary'];
            @endphp
            <span class="badge text-bg-{{ $typeColors[$rapport->type->value] ?? 'secondary' }} fs-6">{{ $rapport->type->getLabel() }}</span>
        </div>
    </div>

    <div class="app-card mb-3">
        <div class="card-body">
            <div class="row g-3 text-center">
                <div class="col-md-4">
                    <div class="text-muted small">{{ __('Student') }}</div>
                    <div class="fw-bold">{{ $rapport->etudiant?->nom_complet_ar }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">{{ __('Halaqa') }}</div>
                    <div class="fw-bold">{{ $rapport->groupe?->nom_ar }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">{{ __('Period') }}</div>
                    <div>{{ $rapport->date_debut->translatedFormat('d/m/Y') }} — {{ $rapport->date_fin->translatedFormat('d/m/Y') }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="app-card mb-3">
        <div class="card-header bg-transparent px-3 py-2">{{ __('Attendance') }}</div>
        <div class="card-body">
            <div class="row g-3 text-center">
                <div class="col-3">
                    <div class="fs-4 fw-bold text-primary">{{ $rapport->nb_seances }}</div>
                    <div class="text-muted small">{{ __('Sessions') }}</div>
                </div>
                <div class="col-3">
                    <div class="fs-4 fw-bold text-success">
                        {{ $rapport->taux_presence !== null ? $rapport->taux_presence.'%' : '—' }}
                    </div>
                    <div class="text-muted small">{{ __('Presences') }} ({{ $rapport->nb_presences }}/{{ $rapport->nb_seances }})</div>
                </div>
                <div class="col-3">
                    <div class="fs-4 fw-bold text-danger">{{ $rapport->nb_absences }}</div>
                    <div class="text-muted small">{{ __('Absences') }}</div>
                </div>
                <div class="col-3">
                    <div class="fs-4 fw-bold text-warning">{{ $rapport->nb_retards }}</div>
                    <div class="text-muted small">{{ __('Lateness') }}</div>
                </div>
            </div>
        </div>
    </div>

    @if ($sessions->isNotEmpty())
        <div class="app-card mb-3">
            <div class="card-header bg-transparent px-3 py-2">{{ __('Student sessions during the period') }}</div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Attendance') }}</th>
                            <th>{{ __('Behavior') }}</th>
                            <th>{{ __('Recited') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sessions as $seance)
                            <tr>
                                <td>{{ $seance->date->translatedFormat('l d/m/Y') }}</td>
                                <td>
                                    <span class="badge text-bg-{{ $seance->presence->getColor() }}">{{ $seance->presence->getLabel() }}</span>
                                </td>
                                <td>{{ $seance->note_comportement?->getLabel() ?? '—' }}</td>
                                <td class="small">
                                    @forelse ($seance->lignes as $lg)
                                        <div>{{ $lg->type->getLabel() }} : {{ $lg->sourateDebut->numero }}.{{ $lg->ayah_debut }} → {{ $lg->sourateFin->numero }}.{{ $lg->ayah_fin }}</div>
                                    @empty
                                        <span class="text-muted">—</span>
                                    @endforelse
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="app-card mb-3">
        <div class="card-header bg-transparent px-3 py-2">{{ __('Memorization and revision') }}</div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th></th>
                        <th>{{ __('New memorization') }}</th>
                        <th>{{ __('Revision') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ __('Pages') }}</td>
                        <td>{{ $rapport->total_pages_hifd }}</td>
                        <td>{{ $rapport->total_pages_murajaa }}</td>
                    </tr>
                    <tr>
                        <td>{{ __('Level') }}</td>
                        <td>
                            @if ($rapport->moyenne_hifd)
                                <span class="badge text-bg-{{ $rapport->moyenne_hifd->getColor() }}">{{ $rapport->moyenne_hifd->getLabel() }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if ($rapport->moyenne_murajaa)
                                <span class="badge text-bg-{{ $rapport->moyenne_murajaa->getColor() }}">{{ $rapport->moyenne_murajaa->getLabel() }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="app-card mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="text-muted small">{{ __('Behavior level') }}</div>
                    <div>
                        @if ($rapport->moyenne_comportement)
                            <span class="badge text-bg-{{ $rapport->moyenne_comportement->getColor() }} fs-6">{{ $rapport->moyenne_comportement->getLabel() }}</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="text-muted small">{{ __('Overall appreciation') }}</div>
                    <div class="fs-5 fw-bold text-primary">{{ $rapport->appreciation ?? '—' }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="text-muted small">
        {{ __('Generated by') }}: {{ $rapport->generePar?->nom_ar ?? __('System') }}
    </div>
@endsection
