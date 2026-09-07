@extends('layouts.app')

@section('title', __('Daily reports'))
@section('page-title', __('Daily reports'))

@section('content')
    <div class="app-card mb-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label small">{{ __('Search for a student (name or registration number)') }}</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="search" name="q" value="{{ $filtres['q'] ?? '' }}" class="form-control" placeholder="{{ __('Example: Mohamed or 0007') }}">
                </div>
            </div>
            <div class="col-md-2">
                <label class="form-label small">{{ __('From date') }}</label>
                <input type="date" name="debut" value="{{ $filtres['debut'] ?? '' }}" class="form-control">
            </div>
            <div class="col-md-2">
                <label class="form-label small">{{ __('To date') }}</label>
                <input type="date" name="fin" value="{{ $filtres['fin'] ?? '' }}" class="form-control">
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-1"></i>{{ __('Search') }}</button>
                <a href="{{ route('rapport-journaliers.index') }}" class="btn btn-outline-secondary" title="{{ __('Cancel') }}"><i class="bi bi-x-lg"></i></a>
            </div>
            <div class="col-md-1">
                <a href="{{ route('rapport-journaliers.excel', $filtres) }}" class="btn btn-outline-success w-100" title="{{ __('Download Excel') }}"><i class="bi bi-file-earmark-excel"></i></a>
            </div>
        </form>
    </div>

    <div class="d-flex justify-content-end mb-2">
        @if (auth()->user()->estProfesseur())
            <a href="{{ route('rapport-journaliers.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i>{{ __('New daily report') }}
            </a>
        @endif
    </div>

    @php
        $presences = [\App\Enums\StatutPresence::PRESENT->value => ['success', __('present')],
                      \App\Enums\StatutPresence::ABSENT->value => ['danger', __('absent')],
                      \App\Enums\StatutPresence::RETARD->value => ['warning', __('Late')],
                      \App\Enums\StatutPresence::EXCUSE->value => ['info', __('Excused absence')]];
    @endphp

    <div class="app-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>{{ __('Date') }}</th>
                        <th>{{ __('Student') }}</th>
                        <th>{{ __('Halaqa') }}</th>
                        <th>{{ __('Attendance') }}</th>
                        <th>{{ __('Behavior') }}</th>
                        <th>{{ __('Overall average') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rapports as $r)
                        <tr>
                            <td>{{ $r->date->translatedFormat('d/m/Y') }}</td>
                            <td class="fw-semibold">{{ $r->etudiant?->nom_complet_ar }}</td>
                            <td>{{ $r->groupe?->nom_ar }}</td>
                            <td>
                                @php [$c, $l] = $presences[$r->presence->value] ?? ['secondary', $r->presence->value]; @endphp
                                <span class="badge text-bg-{{ $c }}">{{ $l }}</span>
                            </td>
                            <td>
                                @if ($r->note_comportement)
                                    <span class="badge text-bg-{{ $r->note_comportement->getColor() }}">{{ $r->note_comportement->getLabel() }}</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                @if ($r->note_globale !== null)
                                    @php $niveau = \App\Enums\NiveauMoyenne::depuisNote((float) $r->note_globale); @endphp
                                    <span class="badge text-bg-{{ $niveau->getColor() }}">{{ $niveau->getLabel() }}</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('rapport-journaliers.show', $r) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                                @if ($r->professeur_id === auth()->id())
                                    <a href="{{ route('rapport-journaliers.edit', $r) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                                    <form method="POST" action="{{ route('rapport-journaliers.destroy', $r) }}" class="d-inline"
                                          onsubmit="return confirm('{{ __('Delete this report?') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">{{ __('No reports in this period.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $rapports->links() }}</div>
    </div>
@endsection
