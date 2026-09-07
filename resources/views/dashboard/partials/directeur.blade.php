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
                    <th>{{ __('Capacity') }}</th>
                    <th>{{ __('Present') }}</th>
                    <th>{{ __('Absent') }}</th>
                    <th>{{ __('Today\'s reports') }}</th>
                    <th>{{ __('Monthly average') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($parHalaqa as $ligne)
                    <tr>
                        <td class="fw-semibold">{{ $ligne->groupe }}</td>
                        <td>{{ $ligne->professeur ?? '—' }}</td>
                        <td>
                            <span class="badge text-bg-light">{{ $ligne->etudiants }}</span>
                            @if ($ligne->capacite)
                                <span class="text-muted small">{{ round(($ligne->etudiants / max(1, $ligne->capacite)) * 100) }} %</span>
                            @endif
                        </td>
                        <td>{{ $ligne->capacite ?? '—' }}</td>
                        <td class="text-success">{{ $ligne->presents }}</td>
                        <td class="text-danger">{{ $ligne->absents }}</td>
                        <td>
                            @php $cls = $ligne->rapports >= $ligne->etudiants && $ligne->etudiants > 0 ? 'success' : 'warning'; @endphp
                            <span class="badge text-bg-{{ $cls }}">{{ $ligne->rapports }} / {{ $ligne->etudiants }}</span>
                        </td>
                        <td>
                            @php $cls = $ligne->moyenneMois === null ? 'light' : ($ligne->moyenneMois >= 14 ? 'success' : ($ligne->moyenneMois >= 10 ? 'warning' : 'danger')); @endphp
                            <span class="badge text-bg-{{ $cls }}">{{ $ligne->moyenneMois ?? '—' }}</span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">{{ __('No halaqas yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Derniers rapports journaliers --}}
<div class="app-card mb-4">
    <div class="card-header px-3 py-2">
        <i class="bi bi-clock-history me-1"></i>
        {{ __('Recent reports') }}
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>{{ __('Date') }}</th>
                    <th>{{ __('Student') }}</th>
                    <th>{{ __('Halaqa') }}</th>
                    <th>{{ __('Teacher') }}</th>
                    <th>{{ __('Presence') }}</th>
                    <th>{{ __('Overall average') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($derniersRapports as $r)
                    <tr>
                        <td class="text-nowrap">{{ $r->date->format('d/m/Y') }}</td>
                        <td class="fw-semibold">
                            <a href="{{ route('etudiants.show', $r->etudiant_id) }}">{{ $r->etudiant?->nom_ar ?: $r->etudiant?->nom_complet }}</a>
                        </td>
                        <td>
                            @if ($r->groupe)
                                <span class="badge text-bg-light">{{ $r->groupe->nom_ar }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>{{ $r->professeur?->nom_ar ?: ($r->professeur?->nom_complet ?? '—') }}</td>
                        <td>
                            @if ($r->presence)
                                <span class="badge {{ $r->presence->value === 'present' ? 'text-bg-success' : ($r->presence->value === 'absent' ? 'text-bg-danger' : 'text-bg-secondary') }}">
                                    {{ $r->presence->getLabel() }}
                                </span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>{{ $r->note_globale ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">{{ __('No reports yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Tableau de progression (top 15) --}}
<div class="app-card mb-4">
    <div class="card-header px-3 py-2">
        <i class="bi bi-trophy me-1"></i>
        {{ __('Student memorization progress') }}
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>{{ __('Student') }}</th>
                    <th>{{ __('Halaqa') }}</th>
                    <th>{{ __('Memorized verses') }}</th>
                    <th>{{ __('Completion percentage') }}</th>
                    <th>{{ __('Daily average (page)') }}</th>
                    <th>{{ __('Monthly average') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($progression as $ligne)
                    <tr>
                        <td class="fw-semibold">{{ $ligne->nom }}</td>
                        <td>
                            @if ($ligne->groupe)
                                <span class="badge text-bg-light">{{ $ligne->groupe }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>{{ $ligne->versets }}</td>
                        <td>
                            @php
                                $cls = $ligne->pourcentage >= 50 ? 'success' : ($ligne->pourcentage >= 20 ? 'info' : 'warning');
                            @endphp
                            <span class="badge text-bg-{{ $cls }}">{{ $ligne->pourcentage }} %</span>
                        </td>
                        <td>{{ $ligne->rythme }}</td>
                        <td>
                            @php
                                $cls = $ligne->moyenne === null ? 'light' : ($ligne->moyenne >= 14 ? 'success' : ($ligne->moyenne >= 10 ? 'warning' : 'danger'));
                            @endphp
                            <span class="badge text-bg-{{ $cls }}">{{ $ligne->moyenne ?? '—' }}</span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">{{ __('No accepted students yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Répartition des statuts d'inscription --}}
<div class="app-card">
    <div class="card-header px-3 py-2">
        <i class="bi bi-pie-chart-fill me-1"></i>
        {{ __('Status breakdown') }}
    </div>
    <div class="card-body">
        <div class="d-flex flex-wrap gap-2">
            @forelse ($statuts as $s)
                <span class="badge text-bg-light fs-6">
                    {{ $s->statut }} : <strong>{{ $s->total }}</strong>
                </span>
            @empty
                <p class="text-muted mb-0">{{ __('No accepted students yet.') }}</p>
            @endforelse
        </div>
    </div>
</div>