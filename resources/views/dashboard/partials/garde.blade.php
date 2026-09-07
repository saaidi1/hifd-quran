{{-- Lits par chambre --}}
<div class="app-card mb-4">
    <div class="card-header px-3 py-2">
        <i class="bi bi-house-door-fill me-1"></i>
        {{ __('Beds per room') }}
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>{{ __('Room') }}</th>
                    <th>{{ __('Building') }}</th>
                    <th>{{ __('Capacity') }}</th>
                    <th>{{ __('Occupied') }}</th>
                    <th>{{ __('Free') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($litsParChambre as $c)
                    <tr>
                        <td class="fw-semibold">{{ $c->numero }}</td>
                        <td>{{ $c->batiment ?? '—' }}</td>
                        <td>{{ $c->capacite }}</td>
                        <td class="text-danger">{{ $c->occupes }}</td>
                        <td class="text-success">{{ $c->libres }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">{{ __('No rooms yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="row g-3 mb-4">
    {{-- Internes --}}
    <div class="col-12 col-lg-6">
        <div class="app-card h-100">
            <div class="card-header px-3 py-2">
                <i class="bi bi-bed me-1"></i>
                {{ __('Boarding students') }}
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Student') }}</th>
                            <th>{{ __('Bed number') }}</th>
                            <th>{{ __('Room') }}</th>
                            <th>{{ __('Halaqa') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($internes as $e)
                            <tr>
                                <td class="fw-semibold">{{ $e->nom_ar ?: $e->nom_complet }}</td>
                                <td>{{ $e->hebergement?->numero_lit ?? '—' }}</td>
                                <td>{{ $e->hebergement?->chambre?->numero ?? '—' }}</td>
                                <td><span class="badge text-bg-light">{{ $e->groupe?->nom_ar ?? '—' }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">{{ __('No boarding students.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Absences aujourd'hui --}}
    <div class="col-12 col-lg-6">
        <div class="app-card h-100">
            <div class="card-header px-3 py-2">
                <i class="bi bi-exclamation-triangle text-warning me-1"></i>
                {{ __('Today\'s absences') }}
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Student') }}</th>
                            <th>{{ __('Halaqa') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($absencesJour as $r)
                            <tr>
                                <td class="fw-semibold">{{ $r->etudiant?->nom_ar ?: $r->etudiant?->nom_complet }}</td>
                                <td><span class="badge text-bg-light">{{ $r->groupe?->nom_ar ?? '—' }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="text-center text-muted py-4">{{ __('No absences today.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    {{-- Dernières inscriptions --}}
    <div class="col-12 col-lg-6">
        <div class="app-card h-100">
            <div class="card-header px-3 py-2">
                <i class="bi bi-person-plus-fill me-1"></i>
                {{ __('Latest registrations') }}
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Student') }}</th>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Registered by') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($preinscriptions as $e)
                            <tr>
                                <td class="fw-semibold">{{ $e->nom_ar ?: $e->nom_complet }}</td>
                                <td>{{ $e->created_at->format('d/m/Y') }}</td>
                                <td>{{ $e->preinscritPar?->nom_ar ?: $e->preinscritPar?->nom_complet ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted py-4">{{ __('No accepted students yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Acceptés sans حلقة à affecter --}}
    <div class="col-12 col-lg-6">
        <div class="app-card h-100">
            <div class="card-header px-3 py-2">
                <i class="bi bi-arrow-right-circle-fill text-info me-1"></i>
                {{ __('Accepted without halaqa') }}
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Student') }}</th>
                            <th>{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($aAffecter as $e)
                            <tr>
                                <td class="fw-semibold">{{ $e->nom_ar ?: $e->nom_complet }}</td>
                                <td>
                                    <a href="{{ route('etudiants.show', $e->id) }}" class="btn btn-sm btn-outline-info">
                                        <i class="bi bi-link-45deg me-1"></i>{{ __('Assign') }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="text-center text-muted py-4">{{ __('No accepted students yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>