{{-- Assiduité 30 jours --}}
<div class="app-card mb-4">
    <div class="card-header px-3 py-2">
        <i class="bi bi-calendar-week me-1"></i>
        {{ __('Attendance over 30 days') }}
    </div>
    <div class="card-body">
        @if (isset($assiduite) && count($assiduite['labels']))
            @php
                $max = max(1, max(array_merge($assiduite['presents'], $assiduite['absents'])));
            @endphp
            <div class="d-flex align-items-end gap-1" style="height: 140px;">
                @foreach ($assiduite['labels'] as $i => $label)
                    <div class="flex-grow-1 d-flex flex-column justify-content-end"
                         title="{{ $label }} : {{ __('present') }} {{ $assiduite['presents'][$i] }} / {{ __('absent') }} {{ $assiduite['absents'][$i] }}">
                        <div class="bg-danger rounded-top-1 mx-auto" style="width: 6px; height: {{ ($assiduite['absents'][$i] / $max) * 100 }}%;"></div>
                        <div class="bg-primary rounded-bottom-1 mx-auto" style="width: 6px; height: {{ ($assiduite['presents'][$i] / $max) * 100 }}%;"></div>
                    </div>
                @endforeach
            </div>
            <div class="d-flex gap-3 mt-2 small text-muted">
                <span><span class="d-inline-block bg-primary rounded" style="width:10px;height:10px;"></span> {{ __('Present') }}</span>
                <span><span class="d-inline-block bg-danger rounded" style="width:10px;height:10px;"></span> {{ __('Absent') }}</span>
            </div>
        @else
            <p class="text-muted mb-0">{{ __('No attendance data yet.') }}</p>
        @endif
    </div>
</div>

{{-- Tests en attente d'évaluation --}}
<div class="app-card mb-4">
    <div class="card-header px-3 py-2">
        <i class="bi bi-clipboard-check me-1"></i>
        {{ __('Tests awaiting evaluation') }}
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>{{ __('Student') }}</th>
                    <th>{{ __('Date') }}</th>
                    <th>{{ __('Registered by') }}</th>
                    <th>{{ __('Action') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($evaluations as $e)
                    <tr>
                        <td class="fw-semibold">{{ $e->nom_ar ?: $e->nom_complet }}</td>
                        <td>{{ $e->created_at->format('d/m/Y') }}</td>
                        <td>{{ $e->preinscritPar?->nom_ar ?: $e->preinscritPar?->nom_complet ?? '—' }}</td>
                        <td>
                            <a href="{{ route('etudiants.show', $e->id) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye me-1"></i>{{ __('Evaluate') }}
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-4">{{ __('No tests awaiting evaluation yet') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Synthèse par حلقة --}}
<div class="app-card mb-4">
    <div class="card-header px-3 py-2">
        <i class="bi bi-diagram-3-fill me-1"></i>
        {{ __('Overview by halaqa') }}
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>{{ __('Halaqa') }}</th>
                    <th>{{ __('Teacher') }}</th>
                    <th>{{ __('Students') }}</th>
                    <th>{{ __('Present') }}</th>
                    <th>{{ __('Monthly average') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($parHalaqa as $ligne)
                    <tr>
                        <td class="fw-semibold">{{ $ligne->groupe }}</td>
                        <td>{{ $ligne->professeur ?? '—' }}</td>
                        <td><span class="badge text-bg-light">{{ $ligne->etudiants }}</span></td>
                        <td class="text-success">{{ $ligne->presents }} / {{ $ligne->etudiants }}</td>
                        <td>
                            @php $cls = $ligne->moyenneMois === null ? 'light' : ($ligne->moyenneMois >= 14 ? 'success' : ($ligne->moyenneMois >= 10 ? 'warning' : 'danger')); @endphp
                            <span class="badge text-bg-{{ $cls }}">{{ $ligne->moyenneMois ?? '—' }}</span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">{{ __('No halaqas yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Professeurs et groupes --}}
<div class="app-card">
    <div class="card-header px-3 py-2">
        <i class="bi bi-person-workspace me-1"></i>
        {{ __('Supervisors and teachers') }}
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>{{ __('Teacher') }}</th>
                    <th>{{ __('Role') }}</th>
                    <th>{{ __('Halaqas') }}</th>
                    <th>{{ __('Students') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($encadrants as $u)
                    <tr>
                        <td class="fw-semibold">{{ $u->nom }}</td>
                        <td><span class="badge text-bg-secondary">{{ $u->role }}</span></td>
                        <td><span class="badge text-bg-info">{{ $u->groupes }}</span></td>
                        <td><span class="badge text-bg-primary">{{ $u->etudiants }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-4">{{ __('No teachers yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>