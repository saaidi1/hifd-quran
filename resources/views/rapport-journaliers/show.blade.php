@extends('layouts.app')

@section('title', __('Daily report'))
@section('page-title', __('Daily report'))

@section('content')
    @php
        $presences = [\App\Enums\StatutPresence::PRESENT->value => ['success', __('present')],
                      \App\Enums\StatutPresence::ABSENT->value => ['danger', __('absent')],
                      \App\Enums\StatutPresence::RETARD->value => ['warning', __('Late')],
                      \App\Enums\StatutPresence::EXCUSE->value => ['info', __('Excused absence')]];
    @endphp

    <div class="d-flex justify-content-between mb-3">
        <a href="{{ route('rapport-journaliers.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-right me-1"></i>{{ __('Back') }}</a>
        <div class="d-flex gap-1">
            <a href="{{ route('rapport-journaliers.pdf', $rapport) }}" class="btn btn-outline-danger"><i class="bi bi-filetype-pdf me-1"></i>{{ __('Download PDF') }}</a>
            @if ($rapport->professeur_id === auth()->id())
                <a href="{{ route('rapport-journaliers.edit', $rapport) }}" class="btn btn-outline-secondary"><i class="bi bi-pencil me-1"></i>{{ __('Edit') }}</a>
                <form method="POST" action="{{ route('rapport-journaliers.destroy', $rapport) }}" onsubmit="return confirm('{{ __('Delete this report?') }}');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger"><i class="bi bi-trash me-1"></i>{{ __('Delete') }}</button>
                </form>
            @endif
        </div>
    </div>

    <div class="app-card mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="text-muted small">{{ __('Student') }}</div>
                    <div class="fw-semibold">{{ $rapport->etudiant->nom_complet_ar }}</div>
                </div>
                <div class="col-md-2">
                    <div class="text-muted small">{{ __('Date') }}</div>
                    <div>{{ $rapport->date->translatedFormat('l d/m/Y') }}</div>
                </div>
                <div class="col-md-2">
                    <div class="text-muted small">{{ __('Halaqa') }}</div>
                    <div>{{ $rapport->groupe?->nom_ar }}</div>
                </div>
                <div class="col-md-2">
                    <div class="text-muted small">{{ __('Attendance') }}</div>
                    <div>@php [$c, $l] = $presences[$rapport->presence->value] ?? ['secondary', '']; @endphp
                        <span class="badge text-bg-{{ $c }}">{{ $l }}</span></div>
                </div>
                <div class="col-md-1">
                    <div class="text-muted small">{{ __('Behavior') }}</div>
                    <div>
                        @if ($rapport->note_comportement)
                            <span class="badge text-bg-{{ $rapport->note_comportement->getColor() }}">{{ $rapport->note_comportement->getLabel() }}</span>
                        @else
                            —
                        @endif
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="text-muted small">{{ __('Overall average') }}</div>
                    <div class="fw-bold fs-5">
                        @if ($rapport->note_globale !== null)
                            @php $niveau = \App\Enums\NiveauMoyenne::depuisNote((float) $rapport->note_globale); @endphp
                            <span class="badge text-bg-{{ $niveau->getColor() }}">{{ $niveau->getLabel() }}</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </div>
                </div>
            </div>
            @if ($rapport->remarques)
                <hr>
                <div class="text-muted small">{{ __('Remarks') }}</div>
                <div>{{ $rapport->remarques }}</div>
            @endif
        </div>
    </div>

    <div class="app-card">
        <div class="card-header bg-transparent px-3 py-2">{{ __('Reading details') }}</div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>{{ __('Type') }}</th>
                        <th>{{ __('Read portion') }}</th>
                        <th>{{ __('Pages') }}</th>
                        <th>{{ __('Errors') }}</th>
                        <th>{{ __('Hesitations') }}</th>
                        <th>{{ __('Mastery') }}</th>
                        <th>{{ __('Note') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rapport->lignes as $lg)
                        <tr>
                            <td><span class="badge text-bg-{{ $lg->type->getColor() }}">{{ $lg->type->getLabel() }}</span></td>
                            <td>{{ $lg->sourateDebut->numero }}:{{ $lg->ayah_debut }} → {{ $lg->sourateFin->numero }}:{{ $lg->ayah_fin }}</td>
                            <td>{{ $lg->nb_pages ?? '—' }}</td>
                            <td>{{ $lg->nb_erreurs }}</td>
                            <td>{{ $lg->nb_hesitations }}</td>
                            <td>
                                @if ($lg->maitrise)
                                    <span class="badge text-bg-{{ $lg->maitrise->getColor() }}">{{ $lg->maitrise->getLabel() }}</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-muted">{{ $lg->observation ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">{{ __('No reading details.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
