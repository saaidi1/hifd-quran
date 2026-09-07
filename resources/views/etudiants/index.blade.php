@php
    $couleurs = [
        'preinscrit' => 'warning', 'en_test' => 'info', 'valide' => 'success',
        'refuse' => 'danger', 'ajourne' => 'secondary', 'abandon' => 'secondary',
    ];
    $filtreStatut = request('statut');
@endphp
@extends('layouts.app')

@section('title', __('Students'))
@section('page-title', __('Students'))

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <p class="text-muted mb-0">{{ __('Student register management and acceptance follow-up.') }}</p>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#filtres" aria-expanded="false">
                <i class="bi bi-funnel me-1"></i>{{ __('Filter') }}
            </button>
            @if (auth()->user()->estGarde() || auth()->user()->estDirecteur())
                <button class="btn btn-outline-success" type="button" id="bulkButton">
                    <i class="bi bi-people-fill me-1"></i>{{ __('Bulk assignment') }}
                </button>
            @endif
            @if (! auth()->user()->estProfesseur())
                <a href="{{ route('etudiants.create') }}" class="btn btn-primary">
                    <i class="bi bi-person-plus me-1"></i>{{ __('Register new student') }}
                </a>
            @endif
        </div>
    </div>

    <div class="collapse mb-3 {{ request()->has('statut') || request()->has('groupe_id') || request()->has('interne') || request()->has('sans_groupe') || request()->has('q') ? 'show' : '' }}" id="filtres">
        <div class="app-card">
            <div class="card-body">
                <form method="GET" action="{{ route('etudiants.index') }}" class="row g-2">
                    <div class="col-md-3">
                        <label class="form-label small mb-1">{{ __('Search') }}</label>
                        <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="{{ __('Name or registration number...') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small mb-1">{{ __('Status') }}</label>
                        <select name="statut" class="form-select form-select-sm">
                            <option value="">{{ __('All') }}</option>
                            @foreach (\App\Enums\StatutInscription::cases() as $s)
                                <option value="{{ $s->value }}" @selected($filtreStatut === $s->value)>{{ $s->getLabel() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small mb-1">{{ __('Halaqa') }}</label>
                        <select name="groupe_id" class="form-select form-select-sm">
                            <option value="">{{ __('All') }}</option>
                            @foreach ($groupes as $g)
                                <option value="{{ $g->id }}" @selected(request('groupe_id') == $g->id)>{{ $g->nom_ar }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small mb-1">{{ __('Boarding') }}</label>
                        <select name="interne" class="form-select form-select-sm">
                            <option value="">{{ __('All') }}</option>
                            <option value="1" @selected(request('interne') === '1')>{{ __('Boarding student') }}</option>
                            <option value="0" @selected(request('interne') === '0')>{{ __('Not boarding') }}</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end gap-2">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="sans_groupe" value="1" id="sansGroupe" @checked(request('sans_groupe'))>
                            <label class="form-check-label small" for="sansGroupe">{{ __('Accepted without halaqa') }}</label>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">{{ __('Apply') }}</button>
                        <a href="{{ route('etudiants.index') }}" class="btn btn-outline-secondary btn-sm">{{ __('Cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="app-card">
        <div class="table-responsive">
            <form method="POST" action="{{ route('etudiants.affecter-multiple') }}" id="bulkForm">
                @csrf
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            @if (auth()->user()->estGarde() || auth()->user()->estDirecteur())
                                <th style="width:36px;">
                                    <input type="checkbox" class="form-check-input" id="selectAll">
                                </th>
                            @endif
                            <th>{{ __('Registration number') }}</th>
                            <th>{{ __('Student') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Halaqa') }}</th>
                            <th>{{ __('Teacher') }}</th>
                            <th>{{ __('Memorized (hizb)') }}</th>
                            <th>{{ __('Documents') }}</th>
                            <th>{{ __('Boarding') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($etudiants as $e)
                            <tr>
                                @if (auth()->user()->estGarde() || auth()->user()->estDirecteur())
                                    <td>
                                        <input type="checkbox" class="form-check-input row-check" name="ids[]" value="{{ $e->id }}">
                                    </td>
                                @endif
                                <td class="fw-semibold">{{ $e->matricule }}</td>
                                <td>
                                    <a href="{{ route('etudiants.show', $e) }}" class="text-decoration-none fw-semibold">{{ $e->nom_complet_ar }}</a>
                                </td>
                                <td>
                                    <span class="badge text-bg-{{ $couleurs[$e->statut->value] ?? 'secondary' }} status-badge">
                                        {{ $e->statut->getLabel() }}
                                    </span>
                                </td>
                                <td>
                                    @if ($e->groupe)
                                        <span class="badge text-bg-light">{{ $e->groupe->nom_ar }}</span>
                                    @else
                                        <span class="text-muted">{{ __('Unassigned') }}</span>
                                    @endif
                                </td>
                                <td class="text-muted">{{ $e->groupe?->professeur?->nom_ar ?? '—' }}</td>
                                <td>{{ $e->hifd_initial_hizb }}</td>
                                <td>
                                    @php $docs = count($e->documents()); @endphp
                                    <span class="badge text-bg-{{ $e->documentsComplets() ? 'success' : 'warning' }}">{{ $docs }}/4</span>
                                </td>
                                <td>
                                    @if ($e->interne)
                                        <i class="bi bi-house-door-fill text-success" title="{{ __('Boarding student') }}"></i>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('etudiants.show', $e) }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-folder2-open"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">{{ __('No matching students.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </form>
        </div>

        @if ($etudiants->hasPages())
            <div class="card-footer d-flex justify-content-between align-items-center">
                <div class="text-muted small">{{ __('Total') }} : {{ $etudiants->total() }}</div>
                <div>{{ $etudiants->links() }}</div>
            </div>
        @endif
    </div>

    @if (auth()->user()->estGarde() || auth()->user()->estDirecteur())
        <div class="modal fade" id="bulkAffecterModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('etudiants.affecter-multiple') }}">
                        @csrf
                        <input type="hidden" name="ids[]" value="" id="bulkIds">
                        <div class="modal-header">
                            <h5 class="modal-title">{{ __('Bulk assignment to a halaqa') }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">{{ __('Halaqa') }}</label>
                                <select name="groupe_id" class="form-select" required>
                                    @foreach (\App\Models\Groupe::where('actif', true)->get() as $g)
                                        <option value="{{ $g->id }}">{{ $g->nom_ar }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <p class="text-muted small mb-0" id="bulkCount"></p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                            <button type="submit" class="btn btn-success">{{ __('Assign') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const selectAll = document.getElementById('selectAll');
    const rows = document.querySelectorAll('.row-check');
    const bulkButton = document.getElementById('bulkButton');
    const modal = document.getElementById('bulkAffecterModal');
    const bulkModalForm = modal?.querySelector('form');

    if (selectAll && rows.length) {
        selectAll.addEventListener('change', () => {
            rows.forEach(r => r.checked = selectAll.checked);
        });
        rows.forEach(r => r.addEventListener('change', () => {
            selectAll.checked = [...rows].every(x => x.checked);
        }));
    }

    if (bulkButton && modal) {
        bulkButton.addEventListener('click', () => {
            const checked = [...rows].filter(r => r.checked).map(r => r.value);
            if (!checked.length) {
                alert('{{ __('Please select at least one student.') }}');
                return;
            }
            document.getElementById('bulkIds').value = checked.join(',');
            document.getElementById('bulkCount').textContent = checked.length + ' {{ __('students will be assigned.') }}';
            new bootstrap.Modal(modal).show();
        });
    }

    if (bulkModalForm) {
        bulkModalForm.addEventListener('submit', function () {
            const hidden = document.getElementById('bulkIds');
            const ids = hidden.value.split(',');
            hidden.remove();
            ids.forEach(id => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = id;
                this.appendChild(input);
            });
        });
    }
});
</script>
@endpush
