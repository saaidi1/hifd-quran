@extends('layouts.app')

@section('title', __('Periodic reports'))
@section('page-title', __('Periodic reports'))

@section('content')
    <div class="app-card mb-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small">{{ __('Type') }}</label>
                <select name="type" class="form-select">
                    <option value="">{{ __('All') }}</option>
                    @foreach (\App\Enums\TypeRapportPeriodique::cases() as $t)
                        <option value="{{ $t->value }}" @selected(($filtres['type'] ?? '') == $t->value)>{{ $t->getLabel() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">{{ __('Halaqa') }}</label>
                <select name="groupe_id" class="form-select">
                    <option value="">{{ __('All') }}</option>
                    @foreach ($groupes as $g)
                        <option value="{{ $g->id }}" @selected(($filtres['groupe_id'] ?? '') == $g->id)>{{ $g->nom_ar }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">{{ __('Month') }}</label>
                <input type="month" name="mois" value="{{ $filtres['mois'] ?? '' }}" class="form-control">
            </div>
            <div class="col-md-3">
                <label class="form-label small">{{ __('Search') }}</label>
                <input type="text" name="q" value="{{ $filtres['q'] ?? '' }}" class="form-control" placeholder="{{ __('Name or registration number') }}">
            </div>
            <div class="col-md-auto">
                <button type="submit" class="btn btn-outline-primary"><i class="bi bi-funnel me-1"></i>{{ __('Filter') }}</button>
                <a href="{{ route('rapports-periodiques.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                <a href="{{ route('rapports-periodiques.excel', $filtres) }}" class="btn btn-outline-success"><i class="bi bi-file-earmark-excel me-1"></i>{{ __('Download Excel') }}</a>
            </div>
        </form>
    </div>

    <div class="app-card mb-3">
        <div class="card-body">
            <h6 class="mb-2">{{ __('Generate reports') }}</h6>
            <form method="POST" action="{{ route('rapports-periodiques.generer') }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-3">
                    <label class="form-label small">{{ __('Type') }}</label>
                    <select name="type" class="form-select" required>
                        @foreach (\App\Enums\TypeRapportPeriodique::cases() as $t)
                            <option value="{{ $t->value }}">{{ $t->getLabel() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">{{ __('Month') }}</label>
                    <input type="month" name="mois" value="{{ $filtres['mois'] ?? now()->format('Y-m') }}" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">{{ __('Halaqa (optional)') }}</label>
                    <select name="groupe" class="form-select">
                        <option value="">{{ __('All') }}</option>
                        @foreach ($groupes as $g)
                            <option value="{{ $g->id }}">{{ $g->nom_ar }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-auto">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-magic me-1"></i>{{ __('Generate') }}</button>
                </div>
            </form>
        </div>
    </div>

    <div class="app-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>{{ __('Student') }}</th>
                        <th>{{ __('Halaqa') }}</th>
                        <th>{{ __('Type') }}</th>
                        <th>{{ __('Period') }}</th>
                        <th>{{ __('Attendance') }}</th>
                        <th>{{ __('Memorized pages') }}</th>
                        <th>{{ __('Memorization level') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rapports as $r)
                        <tr>
                            <td class="fw-semibold">{{ $r->etudiant?->nom_complet_ar }}</td>
                            <td>{{ $r->groupe?->nom_ar }}</td>
                            <td>
                                @php $typeColors = ['hebdomadaire' => 'info', 'mensuel' => 'dark', 'annuel' => 'primary']; @endphp
                                <span class="badge text-bg-{{ $typeColors[$r->type->value] ?? 'secondary' }}">{{ $r->type->getLabel() }}</span>
                            </td>
                            <td class="small">{{ $r->date_debut->translatedFormat('d/m') }} — {{ $r->date_fin->translatedFormat('d/m/Y') }}</td>
                            <td class="small">
                                @if ($r->taux_presence !== null)
                                    <span class="badge text-bg-{{ $r->taux_presence >= 75 ? 'success' : ($r->taux_presence >= 50 ? 'warning' : 'danger') }}"
                                          title="{{ $r->nb_presences }}/{{ $r->nb_seances }}">{{ $r->taux_presence }}%</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $r->total_pages_hifd }}</td>
                            <td>
                                @if ($r->moyenne_hifd)
                                    <span class="badge text-bg-{{ $r->moyenne_hifd->getColor() }}">{{ $r->moyenne_hifd->getLabel() }}</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('rapports-periodiques.show', $r) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">{{ __('No reports. Use "Generate" to create them.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $rapports->links() }}</div>
    </div>
@endsection
