@extends('layouts.app')

@section('title', __('Student file'))
@section('page-title', __('Student file'))

@section('content')
    @php
        $user = auth()->user();
        $statutCouleurs = [
            'preinscrit' => 'warning', 'en_test' => 'info', 'valide' => 'success',
            'refuse' => 'danger', 'ajourne' => 'secondary', 'abandon' => 'secondary',
        ];
    @endphp

    <div class="app-card mb-3">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-center gap-3">
                <img src="{{ $etudiant->photo ? asset('storage/' . $etudiant->photo) : asset('images/avatar.png') }}"
                     alt="" class="rounded-circle object-fit-cover" style="width:72px;height:72px;">

                <div class="flex-grow-1">
                    <h4 class="mb-1">{{ $etudiant->nom_complet_ar }}</h4>
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <span class="badge text-bg-primary">{{ $etudiant->matricule }}</span>
                        <span class="badge text-bg-{{ $statutCouleurs[$etudiant->statut->value] ?? 'secondary' }}">{{ $etudiant->statut->getLabel() }}</span>
                        @if ($etudiant->groupe)
                            <span class="badge text-bg-light">{{ $etudiant->groupe->nom_ar }}</span>
                        @endif
                        @if ($etudiant->interne)
                            <span class="badge text-bg-info">{{ __('Boarding student') }}</span>
                        @endif
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('etudiants.edit', $etudiant) }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-pencil me-1"></i>{{ __('Edit') }}
                    </a>

                    @if ($user->estSuperviseur() && ! in_array($etudiant->statut->value, ['valide', 'refuse'], true))
                        <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#evaluerModal">
                            <i class="bi bi-clipboard-check me-1"></i>{{ __('Test and accept') }}
                        </button>
                    @endif

                    @if (($user->estGarde() || $user->estDirecteur()) && $etudiant->peutEtreAffecte())
                        <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#affecterModal">
                            <i class="bi bi-people me-1"></i>{{ __('Assign to halaqa') }}
                        </button>
                    @endif

                    @if ($user->estGarde())
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#hebergementModal">
                                <i class="bi bi-house-door me-1"></i>{{ __('Accommodation') }}
                            </button>
                            <button class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#placePriereModal">
                                <i class="bi bi-geo-alt me-1"></i>{{ __('Prayer place') }}
                            </button>
                        </div>
                    @endif

                    <a href="{{ route('etudiants.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-right me-1"></i>{{ __('Back') }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-5">
            <div class="app-card mb-3">
                <div class="card-header px-3 py-2"><i class="bi bi-person-vcard me-1"></i>{{ __('Personal information') }}</div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-5">{{ __('Full name') }}</dt><dd class="col-7">{{ $etudiant->nom_complet_ar }}</dd>
                        <dt class="col-5">{{ __('Name (Latin)') }}</dt><dd class="col-7">{{ $etudiant->nom_complet ?: '—' }}</dd>
                        <dt class="col-5">{{ __('Date of birth') }}</dt><dd class="col-7">{{ $etudiant->date_naissance?->format('d/m/Y') ?? '—' }}</dd>
                        <dt class="col-5">{{ __('Place of birth') }}</dt><dd class="col-7">{{ $etudiant->lieu_naissance ?? '—' }}</dd>
                        <dt class="col-5">{{ __('Gender') }}</dt><dd class="col-7">{{ $etudiant->sexe === 'M' ? __('Male') : __('Female') }}</dd>
                        <dt class="col-5">{{ __('National ID') }}</dt><dd class="col-7">{{ $etudiant->cin ?? '—' }}</dd>
                        <dt class="col-5">{{ __('Phone') }}</dt><dd class="col-7" dir="ltr">{{ $etudiant->telephone ?? '—' }}</dd>
                        <dt class="col-5">{{ __('City') }}</dt><dd class="col-7">{{ $etudiant->ville ?? '—' }}</dd>
                        <dt class="col-5">{{ __('School level') }}</dt><dd class="col-7">{{ $etudiant->niveau_scolaire ?? '—' }}</dd>
                        <dt class="col-5">{{ __('Address') }}</dt><dd class="col-7">{{ $etudiant->adresse ?? '—' }}</dd>
                        <dt class="col-5">{{ __('Registration date') }}</dt><dd class="col-7">{{ $etudiant->date_preinscription?->format('d/m/Y') ?? '—' }}</dd>
                    </dl>
                </div>
            </div>

            <div class="app-card mb-3">
                <div class="card-header px-3 py-2"><i class="bi bi-person-check me-1"></i>{{ __('Guardian') }}</div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-5">{{ __('Name') }}</dt><dd class="col-7">{{ $etudiant->tuteur_nom ?? '—' }}</dd>
                        <dt class="col-5">{{ __('Relationship') }}</dt><dd class="col-7">{{ $etudiant->tuteur_lien ?? '—' }}</dd>
                        <dt class="col-5">{{ __('Phone') }}</dt><dd class="col-7" dir="ltr">{{ $etudiant->tuteur_telephone ?? '—' }}</dd>
                    </dl>
                </div>
            </div>

            <div class="app-card mb-3">
                <div class="card-header px-3 py-2"><i class="bi bi-files me-1"></i>{{ __('Documents') }}</div>
                <div class="card-body">
                    <ul class="list-unstyled small mb-0">
                        @foreach ([
                            ['extrait_naissance', __('Birth certificate')],
                            ['attestation_scolaire', __('School certificate')],
                            ['photo', __('Photo')],
                            ['autre_document', __('Other document')],
                        ] as [$champ, $lib])
                            <li class="d-flex justify-content-between align-items-center py-1 border-bottom">
                                <span>{{ $lib }}</span>
                                @if ($etudiant->$champ)
                                    <a href="{{ asset('storage/' . $etudiant->$champ) }}" target="_blank" class="text-success"><i class="bi bi-check-circle"></i></a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="app-card mb-3">
                <div class="card-header px-3 py-2"><i class="bi bi-mortarboard me-1"></i>{{ __('Situation') }}</div>
                <div class="card-body">
                    <div class="row g-3 text-center">
                        <div class="col-6 col-md-3">
                            <div class="small text-muted">{{ __('Status') }}</div>
                            <div class="fw-semibold">{{ $etudiant->statut->getLabel() }}</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="small text-muted">{{ __('Halaqa') }}</div>
                            <div class="fw-semibold">{{ $etudiant->groupe?->nom_ar ?? __('Unassigned') }}</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="small text-muted">{{ __('Memorized (hizb)') }}</div>
                            <div class="fw-semibold">{{ $etudiant->hifd_initial_hizb }}</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="small text-muted">{{ __('Boarding') }}</div>
                            <div class="fw-semibold">{{ $etudiant->interne ? __('Resident') : __('Not boarding') }}</div>
                        </div>
                        <div class="col-6 col-md-4">
                            <div class="small text-muted">{{ __('Bed') }}</div>
                            <div class="fw-semibold">{{ $etudiant->hebergement ? __('Room :room — Bed :bed', ['room' => $etudiant->hebergement->chambre->numero, 'bed' => $etudiant->hebergement->numero_lit]) : '—' }}</div>
                        </div>
                        <div class="col-6 col-md-4">
                            <div class="small text-muted">{{ __('Prayer place') }}</div>
                            <div class="fw-semibold">{{ $etudiant->placePriere ? __('Row :row — Place :place', ['row' => $etudiant->placePriere->rangee, 'place' => $etudiant->placePriere->numero_place]) : '—' }}</div>
                        </div>
                        <div class="col-6 col-md-4">
                            <div class="small text-muted">{{ __('Test record') }}</div>
                            <div class="fw-semibold">{{ $etudiant->derniereEvaluation()?->decision ?? __('Not tested yet') }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="app-card">
                <div class="card-header px-3 py-2">
                    <ul class="nav nav-tabs card-header-tabs">
                        <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tabEvals">{{ __('Evaluations (:count)', ['count' => $etudiant->evaluations->count()]) }}</a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabRapports">{{ __('Daily reports (:count)', ['count' => $etudiant->rapportsJournaliers->count()]) }}</a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabComport">{{ __('Behavior (:count)', ['count' => $etudiant->comportements->count()]) }}</a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabTaches">{{ __('Assignments (:count)', ['count' => $etudiant->taches->count()]) }}</a></li>
                    </ul>
                </div>
                <div class="tab-content">
                    <div class="tab-pane fade show active p-3" id="tabEvals">
                        @forelse ($etudiant->evaluations as $ev)
                            <div class="border rounded p-2 mb-2 small">
                                <div class="d-flex justify-content-between">
                                    <span class="fw-semibold">{{ $ev->date_test->format('d/m/Y') }}</span>
                                    <span class="badge text-bg-{{ $ev->decision === 'valide' ? 'success' : ($ev->decision === 'refuse' ? 'danger' : 'warning') }}">{{ $ev->decision }}</span>
                                </div>
                                <div class="text-muted">{{ __('Memorization: :hifd / Tajwid: :tajwid / Recitation: :lecture — Mastery: :hizb hizb', ['hifd' => $ev->note_hifd, 'tajwid' => $ev->note_tajwid, 'lecture' => $ev->note_lecture, 'hizb' => $ev->hizb_maitrise]) }}</div>
                                @if ($ev->observations)<div>{{ $ev->observations }}</div>@endif
                                @if ($ev->motif)<div class="text-danger">{{ $ev->motif }}</div>@endif
                            </div>
                        @empty
                            <p class="text-muted text-center my-3">{{ __('No evaluations yet.') }}</p>
                        @endforelse
                    </div>
                    <div class="tab-pane fade p-3" id="tabRapports">
                        @forelse ($etudiant->rapportsJournaliers->take(10) as $r)
                            <div class="border rounded p-2 mb-2 small">
                                <div class="d-flex justify-content-between">
                                    <span class="fw-semibold">{{ $r->date->format('d/m/Y') }}</span>
                                    <span class="badge text-bg-{{ $r->presence->value === 'present' ? 'success' : ($r->presence->value === 'absent' ? 'danger' : 'warning') }}">{{ $r->presence->getLabel() }}</span>
                                </div>
                                @foreach ($r->lignes as $l)
                                    <div class="text-muted">• {{ $l->type->getLabel() }} : {{ $l->libellePlage() }}@if ($l->observation) — {{ $l->observation }}@endif</div>
                                @endforeach
                                @if ($r->remarques)<div>{{ $r->remarques }}</div>@endif
                            </div>
                        @empty
                            <p class="text-muted text-center my-3">{{ __('No reports yet.') }}</p>
                        @endforelse
                    </div>
                    <div class="tab-pane fade p-3" id="tabComport">
                        @forelse ($etudiant->comportements as $c)
                            <div class="border rounded p-2 mb-2 small">
                                <div class="d-flex justify-content-between">
                                    <span class="fw-semibold">{{ $c->date->format('d/m/Y') }}</span>
                                    <span class="badge text-bg-{{ $c->type === 'positif' ? 'success' : 'danger' }}">{{ $c->type === 'positif' ? __('Positive') : __('Negative') }}</span>
                                </div>
                                <div>{{ $c->description }}</div>
                                @if ($c->sanction)<div class="text-danger">{{ __('Sanction: :sanction', ['sanction' => $c->sanction]) }}</div>@endif
                            </div>
                        @empty
                            <p class="text-muted text-center my-3">{{ __('No behavioral notes.') }}</p>
                        @endforelse
                    </div>
                    <div class="tab-pane fade p-3" id="tabTaches">
                        @forelse ($etudiant->taches->sortByDesc('date_echeance') as $t)
                            <div class="border rounded p-2 mb-2 small">
                                <div class="d-flex justify-content-between">
                                    <span class="fw-semibold">{{ $t->type->getLabel() }}</span>
                                    <span class="badge text-bg-{{ $t->statut === 'assignee' ? 'warning' : ($t->statut === 'realisee' ? 'success' : 'secondary') }}">{{ $t->statut }}</span>
                                </div>
                                <div class="text-muted">{{ $t->libellePlage() }} — {{ __('Priority: :date', ['date' => $t->date_echeance->format('d/m/Y')]) }}</div>
                            </div>
                        @empty
                            <p class="text-muted text-center my-3">{{ __('No assignments.') }}</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal : اختبار وقبول --}}
    <div class="modal fade" id="evaluerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="{{ route('etudiants.evaluer', $etudiant) }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('Test and accept') }} : {{ $etudiant->nom_complet_ar }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('Test date') }}</label>
                                <input type="date" name="date_test" value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('Tested surah') }}</label>
                                <select name="sourate_testee_id" class="form-select">
                                    <option value="">—</option>
                                    @foreach (\App\Models\Sourate::orderBy('numero')->get() as $s)
                                        <option value="{{ $s->id }}">{{ $s->nom_ar }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('Memorized (hizb)') }}</label>
                                <input type="number" name="hizb_maitrise" min="0" max="60" step="0.01" value="{{ $etudiant->hifd_initial_hizb }}" class="form-control" required>
                            </div>
                            <div class="col-md-8 d-flex gap-2">
                                <div class="flex-fill">
                                    <label class="form-label">{{ __('Memorization score / 20') }}</label>
                                    <input type="number" name="note_hifd" min="0" max="20" step="0.25" class="form-control" required>
                                </div>
                                <div class="flex-fill">
                                    <label class="form-label">{{ __('Tajwid score / 20') }}</label>
                                    <input type="number" name="note_tajwid" min="0" max="20" step="0.25" class="form-control" required>
                                </div>
                                <div class="flex-fill">
                                    <label class="form-label">{{ __('Recitation score / 20') }}</label>
                                    <input type="number" name="note_lecture" min="0" max="20" step="0.25" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">{{ __('Decision') }}</label>
                                <div class="d-flex gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="decision" id="decValide" value="valide" required>
                                        <label class="form-check-label" for="decValide">{{ __('Accept') }}</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="decision" id="decRefuse" value="refuse">
                                        <label class="form-check-label text-danger" for="decRefuse">{{ __('Reject') }}</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="decision" id="decAjourne" value="ajourne">
                                        <label class="form-check-label text-warning" for="decAjourne">{{ __('Postpone') }}</label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6" id="niveauRow" style="display:none;">
                                <label class="form-label">{{ __('Proposed level') }}</label>
                                <input type="text" name="niveau_propose" class="form-control">
                            </div>
                            <div class="col-md-6" id="motifRow" style="display:none;">
                                <label class="form-label">{{ __('Reason for rejection') }}</label>
                                <textarea name="motif" rows="2" class="form-control"></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">{{ __('Observations') }}</label>
                                <textarea name="observations" rows="2" class="form-control"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-warning">{{ __('Save decision') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal : إسناد إلى مجموعة --}}
    <div class="modal fade" id="affecterModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('etudiants.affecter', $etudiant) }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('Assign to halaqa') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">{{ __('Halaqa') }}</label>
                            <select name="groupe_id" class="form-select" required>
                                @foreach (\App\Models\Groupe::where('actif', true)->get() as $g)
                                    <option value="{{ $g->id }}">{{ $g->nom_ar }} — {{ __(':count places available', ['count' => $g->placesRestantes()]) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('Reason') }}</label>
                            <input type="text" name="motif" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-success">{{ __('Assign') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal : الإيواء --}}
    <div class="modal fade" id="hebergementModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('etudiants.hebergement', $etudiant) }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('Accommodation and bed number') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">{{ __('Room') }}</label>
                            <select name="chambre_id" class="form-select" required>
                                <option value="">{{ __('Choose room') }}</option>
                                @foreach (\App\Models\Chambre::where('actif', true)->get() as $c)
                                    <option value="{{ $c->id }}">
                                        {{ __('Room :room — :count beds available', ['room' => $c->numero, 'count' => count($c->litsLibres())]) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('Bed number') }}</label>
                            <input type="text" class="form-control" value="{{ __('Bed number :number', ['number' => $etudiant->numeroInscription()]) }}" readonly>
                            <div class="form-text">{{ __('The bed number always matches the registration number: :number', ['number' => $etudiant->numeroInscription()]) }}</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('Note') }}</label>
                            <input type="text" name="observation" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-success">{{ __('Assign bed') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal : مكان الصلاة --}}
    <div class="modal fade" id="placePriereModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('etudiants.place-priere', $etudiant) }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('Prayer place') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">{{ __('Place') }}</label>
                            <select name="lieu_priere_id" class="form-select" required>
                                @foreach (\App\Models\LieuPriere::where('actif', true)->get() as $l)
                                    <option value="{{ $l->id }}">{{ $l->nom_ar ?? $l->nom }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('Row') }}</label>
                                <input type="number" name="rangee" min="1" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('Place number') }}</label>
                                <input type="text" class="form-control" value="{{ __('Place number :number', ['number' => $etudiant->numeroInscription()]) }}" readonly>
                                <div class="form-text">{{ __('The place number always matches the registration number: :number', ['number' => $etudiant->numeroInscription()]) }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-success">{{ __('Assign place') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // قناع التقييم
    document.querySelectorAll('input[name="decision"]').forEach(r => {
        r.addEventListener('change', () => {
            const d = document.querySelector('input[name="decision"]:checked')?.value;
            document.getElementById('niveauRow').style.display = d === 'valide' ? '' : 'none';
            document.getElementById('motifRow').style.display = (d === 'refuse' || d === 'ajourne') ? '' : 'none';
        });
    });
});
</script>
@endpush
