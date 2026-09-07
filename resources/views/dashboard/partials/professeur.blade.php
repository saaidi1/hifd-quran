{{-- Mes حلقات --}}
<div class="app-card mb-4">
    <div class="card-header px-3 py-2">
        <i class="bi bi-diagram-3-fill me-1"></i>
        {{ __('My halaqas') }}
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>{{ __('Halaqa') }}</th>
                    <th>{{ __('Students') }}</th>
                    <th>{{ __('Capacity') }}</th>
                    <th>{{ __('Present') }}</th>
                    <th>{{ __('Absent') }}</th>
                    <th>{{ __('Monthly average') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($mesGroupes as $g)
                    <tr>
                        <td class="fw-semibold">{{ $g->groupe }}</td>
                        <td><span class="badge text-bg-light">{{ $g->etudiants }}</span></td>
                        <td>{{ $g->capacite ?? '—' }}</td>
                        <td class="text-success">{{ $g->presents }}</td>
                        <td class="text-danger">{{ $g->absents }}</td>
                        <td>
                            @php $cls = $g->moyenneMois === null ? 'light' : ($g->moyenneMois >= 14 ? 'success' : ($g->moyenneMois >= 10 ? 'warning' : 'danger')); @endphp
                            <span class="badge text-bg-{{ $cls }}">{{ $g->moyenneMois ?? '—' }}</span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">{{ __('No halaqas yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="row g-3 mb-4">
    {{-- À compléter aujourd'hui --}}
    <div class="col-12 col-lg-6">
        <div class="app-card h-100">
            <div class="card-header px-3 py-2">
                <i class="bi bi-pencil-square text-warning me-1"></i>
                {{ __('Not recorded today') }}
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Student') }}</th>
                            <th>{{ __('Halaqa') }}</th>
                            <th>{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($aCompleter as $e)
                            <tr>
                                <td class="fw-semibold">{{ $e->nom_complet_ar }}</td>
                                <td><span class="badge text-bg-light">{{ $e->groupe?->nom_ar ?? '—' }}</span></td>
                                <td>
                                    <a href="{{ route('rapport-journaliers.create') }}" class="btn btn-sm btn-outline-warning">
                                        <i class="bi bi-plus-circle me-1"></i>{{ __('Add') }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted py-4">{{ __('Everything is recorded for today.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Aperçu étudiants : rythme + moyenne --}}
    <div class="col-12 col-lg-6">
        <div class="app-card h-100">
            <div class="card-header px-3 py-2">
                <i class="bi bi-graph-up-arrow me-1"></i>
                {{ __('My students') }}
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Student') }}</th>
                            <th>{{ __('Halaqa') }}</th>
                            <th>{{ __('Monthly average') }}</th>
                            <th>{{ __('Pace (pages/week)') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($apercuEtudiants as $s)
                            <tr>
                                <td class="fw-semibold">{{ $s->nom }}</td>
                                <td><span class="badge text-bg-light">{{ $s->groupe ?? '—' }}</span></td>
                                <td>
                                    @php
                                        $cls = $s->moyenne === null ? 'light' : ($s->moyenne >= 14 ? 'success' : ($s->moyenne >= 10 ? 'warning' : 'danger'));
                                    @endphp
                                    <span class="badge text-bg-{{ $cls }}">{{ $s->moyenne ?? '—' }}</span>
                                </td>
                                <td>{{ $s->rythme }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">{{ __('No accepted students yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>