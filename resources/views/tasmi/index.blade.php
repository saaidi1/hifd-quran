@extends('layouts.app')

@section('title', __('Halaqa recitation'))
@section('page-title', __('Halaqa recitation'))

@section('content')
    <div class="app-card mb-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small">{{ __('Halaqa') }}</label>
                <select name="groupe_id" class="form-select" onchange="this.form.submit()">
                    @foreach ($groupes as $g)
                        <option value="{{ $g->id }}" @selected($groupeId == $g->id)>{{ $g->nom_ar }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small">{{ __('Date') }}</label>
                <input type="date" name="date" value="{{ $date }}" max="{{ now()->toDateString() }}" class="form-control" onchange="this.form.submit()">
            </div>
            <div class="col-md-4 d-flex align-items-center text-muted small">
                <i class="bi bi-info-circle me-1"></i>
                {{ __(':count students in the halaqa', ['count' => $etudiants->count()]) }}
                @if ($nbSeances)
                    — <span class="text-success">{{ __('Sessions recorded for this date') }} : {{ $nbSeances }}</span>
                @endif
            </div>
        </form>
    </div>

    @if ($etudiants->isNotEmpty())
        @if (auth()->user()->estProfesseur() || auth()->user()->estSuperviseur())
            <form method="POST" action="{{ route('tasmi.store') }}">
                @csrf
                <input type="hidden" name="groupe_id" value="{{ $groupeId }}">
                <input type="hidden" name="date" value="{{ $date }}">

            @if ($errors->any())
                <div class="alert alert-danger py-2">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="app-card">
                <div class="card-header small text-muted">
                    <i class="bi bi-plus-circle me-1"></i>
                    {{ __('Each save creates a new session for this date.') }}
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('Student') }}</th>
                                <th class="text-center">{{ __('In the session') }}</th>
                                <th>{{ __('Attendance') }}</th>
                                <th>{{ __('Behavior') }}</th>
                                <th style="min-width:340px">{{ __('Readings') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($etudiants as $e)
                                @php($inclus = old('assister.' . $e->id, true))
                                <tr class="{{ $inclus ? '' : 'opacity-50' }}">
                                    <td class="fw-semibold">{{ $e->nom_complet_ar }}</td>
                                    <td class="text-center" style="min-width:60px">
                                        <input type="checkbox" name="assister[{{ $e->id }}]" value="1" class="form-check-input assister-check" @checked($inclus)>
                                    </td>
                                    <td style="min-width:140px">
                                        <select name="presence[{{ $e->id }}]" class="form-select form-select-sm" @disabled(! $inclus)>
                                            @foreach ($presences as $p)
                                                <option value="{{ $p->value }}" @selected(old('presence.' . $e->id, 'present') == $p->value)>{{ $p->getLabel() }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td style="min-width:130px">
                                        <select name="note_comportement[{{ $e->id }}]" class="form-select form-select-sm" @disabled(! $inclus)>
                                            <option value="">—</option>
                                            @foreach ($niveaux as $n)
                                                <option value="{{ $n->value }}" @selected(old('note_comportement.' . $e->id) == $n->value)>{{ $n->getLabel() }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column gap-2 lectures-zone" data-next="1">
                                            <div class="lecture-block d-flex flex-column gap-1 position-relative">
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text" style="min-width:90px">{{ __('Type') }}</span>
                                                    <select name="lignes[{{ $e->id }}][0][type]" class="form-select" @disabled(! $inclus)>
                                                        <option value="">—</option>
                                                        @foreach ($types as $t)
                                                            <option value="{{ $t->value }}">{{ $t->getLabel() }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text" style="min-width:90px">{{ __('Start') }}</span>
                                                    <select name="lignes[{{ $e->id }}][0][sourate_debut_id]" class="form-select" @disabled(! $inclus)>
                                                        <option value="">— {{ __('Surah') }} —</option>
                                                        @foreach ($sourates as $s)
                                                            <option value="{{ $s->id }}">{{ $s->numero }}. {{ $s->nom_ar }}</option>
                                                        @endforeach
                                                    </select>
                                                    <input type="text" name="lignes[{{ $e->id }}][0][ayah_debut]" class="form-control" style="width:90px" placeholder="{{ __('Start ayah') }}" @disabled(! $inclus)>
                                                </div>
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text" style="min-width:90px">{{ __('End') }}</span>
                                                    <select name="lignes[{{ $e->id }}][0][sourate_fin_id]" class="form-select" @disabled(! $inclus)>
                                                        <option value="">— {{ __('Surah') }} —</option>
                                                        @foreach ($sourates as $s)
                                                            <option value="{{ $s->id }}">{{ $s->numero }}. {{ $s->nom_ar }}</option>
                                                        @endforeach
                                                    </select>
                                                    <input type="text" name="lignes[{{ $e->id }}][0][ayah_fin]" class="form-control" style="width:90px" placeholder="{{ __('End ayah') }}" @disabled(! $inclus)>
                                                </div>
                                                <input type="text" name="lignes[{{ $e->id }}][0][observation]" class="form-control form-control-sm" placeholder="{{ __('Note about the reading') }}" @disabled(! $inclus)>
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-primary mt-1 ajouter-lecture" @disabled(! $inclus)>
                                            <i class="bi bi-plus-lg"></i> {{ __('Add reading') }}
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="card-footer d-flex">
                    <button type="submit" class="btn btn-primary ms-auto"><i class="bi bi-save me-1"></i>{{ __('Record session') }}</button>
                </div>
            </div>
            </form>
        @else
            <div class="app-card">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('Student') }}</th>
                                <th>{{ __('Session') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($etudiants as $e)
                                @php($sessions = $existant[$e->id] ?? collect())
                                <tr class="{{ $sessions->isNotEmpty() ? 'table-success' : '' }}">
                                    <td class="fw-semibold align-top">{{ $e->nom_complet_ar }}</td>
                                    <td>
                                        @forelse ($sessions as $r)
                                            <div class="pb-2 mb-2 border-bottom">
                                                <div class="mb-1 d-flex gap-1 flex-wrap align-items-center">
                                                    <span class="badge text-bg-secondary">{{ __('Session') }} {{ $r->seance }}</span>
                                                    <span class="badge text-bg-{{ $r->presence->getColor() }}">{{ $r->presence->getLabel() }}</span>
                                                    @if ($r->note_comportement)
                                                        <span class="badge text-bg-{{ $r->note_comportement->getColor() }}">{{ $r->note_comportement->getLabel() }}</span>
                                                    @endif
                                                </div>
                                                @forelse ($r->lignes as $lg)
                                                    <div class="small mb-1">
                                                        @if ($lg->type)
                                                            <span class="badge text-bg-{{ $lg->type->getColor() }} me-1">{{ $lg->type->getLabel() }}</span>
                                                        @endif
                                                        <span class="text-nowrap">{{ $lg->sourateDebut?->nom_ar }} {{ $lg->ayah_debut }} <i class="bi bi-arrow-left mx-1"></i> {{ $lg->sourateFin?->nom_ar }} {{ $lg->ayah_fin }}</span>
                                                        @if ($lg->note !== null && $lg->note !== '')
                                                            — {{ __('Note') }} : {{ $lg->note }}/20
                                                        @endif
                                                    </div>
                                                @empty
                                                    <div class="small text-muted">—</div>
                                                @endforelse
                                            </div>
                                        @empty
                                            <span class="text-muted">—</span>
                                        @endforelse
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-center text-muted py-4">{{ __('No students in this halaqa.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    @else
        <div class="app-card p-5 text-center text-muted">{{ __('Choose a halaqa to view its students.') }}</div>
    @endif
@endsection

@push('scripts')
    <script>
        function appliquerEtatLigne(tr) {
            var cb = tr.querySelector('.assister-check');
            if (!cb) return;
            var actif = cb.checked;
            tr.classList.toggle('opacity-50', !actif);
            tr.querySelectorAll('td select, td input[type="text"]').forEach(function (el) {
                el.disabled = !actif;
            });
            var btnAjout = tr.querySelector('.ajouter-lecture');
            if (btnAjout) btnAjout.disabled = !actif;
        }

        document.querySelectorAll('.assister-check').forEach(function (cb) {
            cb.addEventListener('change', function () {
                appliquerEtatLigne(this.closest('tr'));
            });
        });

        document.querySelectorAll('.ajouter-lecture').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var tr = this.closest('tr');
                var zone = tr.querySelector('.lectures-zone');
                var idx = parseInt(zone.dataset.next, 10);
                var modele = zone.querySelector('.lecture-block');

                /* Retirer l'ancien bouton de suppression du modèle cloné */
                modele.querySelectorAll('.supprimer-lecture').forEach(function (b) { b.remove(); });

                var clone = modele.cloneNode(true);
                clone.querySelectorAll('[name]').forEach(function (el) {
                    el.name = el.name.replace(/^(lignes\[\d+\])\[\d+\]/, '$1[' + idx + ']');
                    el.value = '';
                });

                var rm = document.createElement('button');
                rm.type = 'button';
                rm.className = 'btn btn-sm btn-outline-danger supprimer-lecture position-absolute top-0';
                rm.style.left = '-30px';
                rm.innerHTML = '<i class="bi bi-x-lg"></i>';
                rm.title = '{{ __('Delete') }}';
                rm.addEventListener('click', function () { clone.remove(); });
                clone.appendChild(rm);

                zone.appendChild(clone);
                zone.dataset.next = idx + 1;
                appliquerEtatLigne(tr);
            });
        });
    </script>
@endpush
