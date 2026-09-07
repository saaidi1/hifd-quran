@php
    $e = $etudiant ?? null;
    $interne = old('interne', $e?->interne ? 1 : 0);
@endphp

<form method="POST" action="{{ $e ? route('etudiants.update', $e) : route('etudiants.store') }}" enctype="multipart/form-data">
    @csrf
    @if ($e) @method('PUT') @endif

    <ul class="nav nav-pills mb-3 flex-wrap" id="wizardTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tab-info" data-bs-toggle="pill" data-bs-target="#pane-info" type="button" role="tab">
                <i class="bi bi-person-vcard me-1"></i>{{ __('Personal information') }}
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-docs" data-bs-toggle="pill" data-bs-target="#pane-docs" type="button" role="tab">
                <i class="bi bi-files me-1"></i>{{ __('Documents') }}
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-tuteur" data-bs-toggle="pill" data-bs-target="#pane-tuteur" type="button" role="tab">
                <i class="bi bi-person-check me-1"></i>{{ __('Guardian') }}
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-situation" data-bs-toggle="pill" data-bs-target="#pane-situation" type="button" role="tab">
                <i class="bi bi-mortarboard me-1"></i>{{ __('Situation') }}
            </button>
        </li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="pane-info" role="tabpanel">
            <div class="app-card mb-3">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">{{ __('Registration number') }}</label>
                            <input type="text" class="form-control" value="{{ $e?->matricule ?? __('Granted automatically') }}" disabled>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('Last name (Arabic)') }} <span class="text-danger">*</span></label>
                            <input type="text" name="nom_ar" value="{{ old('nom_ar', $e?->nom_ar) }}" class="form-control @error('nom_ar') is-invalid @enderror" required>
                            @error('nom_ar')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('First name (Arabic)') }} <span class="text-danger">*</span></label>
                            <input type="text" name="prenom_ar" value="{{ old('prenom_ar', $e?->prenom_ar) }}" class="form-control @error('prenom_ar') is-invalid @enderror" required>
                            @error('prenom_ar')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('Last name (Latin)') }}</label>
                            <input type="text" name="nom" value="{{ old('nom', $e?->nom) }}" class="form-control @error('nom') is-invalid @enderror">
                            @error('nom')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('First name (Latin)') }}</label>
                            <input type="text" name="prenom" value="{{ old('prenom', $e?->prenom) }}" class="form-control @error('prenom') is-invalid @enderror">
                            @error('prenom')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('Gender') }} <span class="text-danger">*</span></label>
                            <select name="sexe" class="form-select @error('sexe') is-invalid @enderror">
                                <option value="M" @selected(old('sexe', $e?->sexe ?? 'M') === 'M')>{{ __('Male') }}</option>
                                <option value="F" @selected(old('sexe', $e?->sexe) === 'F')>{{ __('Female') }}</option>
                            </select>
                            @error('sexe')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('Date of birth') }} <span class="text-danger">*</span></label>
                            <input type="date" name="date_naissance" value="{{ old('date_naissance', $e?->date_naissance?->toDateString()) }}" max="{{ now()->toDateString() }}" class="form-control @error('date_naissance') is-invalid @enderror" required>
                            @error('date_naissance')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('Place of birth') }}</label>
                            <input type="text" name="lieu_naissance" value="{{ old('lieu_naissance', $e?->lieu_naissance) }}" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('National ID number') }}</label>
                            <input type="text" name="cin" value="{{ old('cin', $e?->cin) }}" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('Phone') }}</label>
                            <input type="tel" name="telephone" value="{{ old('telephone', $e?->telephone) }}" class="form-control @error('telephone') is-invalid @enderror">
                            @error('telephone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('City') }}</label>
                            <input type="text" name="ville" value="{{ old('ville', $e?->ville) }}" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('School level') }}</label>
                            <input type="text" name="niveau_scolaire" value="{{ old('niveau_scolaire', $e?->niveau_scolaire) }}" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Address') }}</label>
                            <textarea name="adresse" rows="2" class="form-control">{{ old('adresse', $e?->adresse) }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Photo') }} {{ $e ? '' : '<span class="text-danger">*</span>' }}</label>
                            <input type="file" name="photo" accept="image/*" class="form-control @error('photo') is-invalid @enderror" {{ $e ? '' : 'required' }}>
                            @error('photo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            @if ($e?->photo)
                                <div class="form-text">{{ __('Current photo') }} : <a href="{{ asset('storage/' . $e->photo) }}" target="_blank">{{ __('View') }}</a></div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="pane-docs" role="tabpanel">
            <div class="app-card mb-3">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">{{ __('Birth certificate') }} {{ $e ? '' : '<span class="text-danger">*</span>' }}</label>
                            <input type="file" name="extrait_naissance" accept="application/pdf,image/jpeg,image/png" class="form-control @error('extrait_naissance') is-invalid @enderror" {{ $e ? '' : 'required' }}>
                            @error('extrait_naissance')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            @if ($e?->extrait_naissance)
                                <div class="form-text"><a href="{{ asset('storage/' . $e->extrait_naissance) }}" target="_blank">{{ __('View file') }}</a></div>
                            @endif
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('School certificate') }} {{ $e ? '' : '<span class="text-danger">*</span>' }}</label>
                            <input type="file" name="attestation_scolaire" accept="application/pdf,image/jpeg,image/png" class="form-control @error('attestation_scolaire') is-invalid @enderror" {{ $e ? '' : 'required' }}>
                            @error('attestation_scolaire')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            @if ($e?->attestation_scolaire)
                                <div class="form-text"><a href="{{ asset('storage/' . $e->attestation_scolaire) }}" target="_blank">{{ __('View file') }}</a></div>
                            @endif
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('Other document (optional)') }}</label>
                            <input type="file" name="autre_document" accept="application/pdf,image/jpeg,image/png" class="form-control">
                            @if ($e?->autre_document)
                                <div class="form-text"><a href="{{ asset('storage/' . $e->autre_document) }}" target="_blank">{{ __('View file') }}</a></div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="pane-tuteur" role="tabpanel">
            <div class="app-card mb-3">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">{{ __('Guardian name') }} <span class="text-danger">*</span></label>
                            <input type="text" name="tuteur_nom" value="{{ old('tuteur_nom', $e?->tuteur_nom) }}" class="form-control @error('tuteur_nom') is-invalid @enderror" required>
                            @error('tuteur_nom')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('Relationship') }}</label>
                            <select name="tuteur_lien" class="form-select">
                                <option value="">—</option>
                                @foreach (['père' => __('Father'), 'mère' => __('Mother'), 'frère' => __('Brother'), 'oncle' => __('Uncle'), 'autre' => __('Other')] as $val => $lib)
                                    <option value="{{ $val }}" @selected(old('tuteur_lien', $e?->tuteur_lien) === $val)>{{ $lib }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('Guardian phone') }} <span class="text-danger">*</span></label>
                            <input type="tel" name="tuteur_telephone" value="{{ old('tuteur_telephone', $e?->tuteur_telephone) }}" class="form-control @error('tuteur_telephone') is-invalid @enderror" required>
                            @error('tuteur_telephone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="pane-situation" role="tabpanel">
            <div class="app-card mb-3">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">{{ __('Memorized at registration (hizb)') }}</label>
                            <input type="number" name="hifd_initial_hizb" min="0" max="60" step="0.01" value="{{ old('hifd_initial_hizb', $e?->hifd_initial_hizb ?? 0) }}" class="form-control @error('hifd_initial_hizb') is-invalid @enderror">
                            @error('hifd_initial_hizb')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('Boarding') }}</label>
                            <select name="interne" class="form-select @error('interne') is-invalid @enderror">
                                <option value="0" @selected((int) $interne === 0)>{{ __('Not boarding') }}</option>
                                <option value="1" @selected((int) $interne === 1)>{{ __('Boarding student') }}</option>
                            </select>
                            @error('interne')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="actif" value="1" id="actif" @checked(old('actif', $e?->actif ?? true))>
                                <label class="form-check-label" for="actif">{{ __('Active') }}</label>
                            </div>
                        </div>
                    </div>
                    @if ($e)
                        <div class="alert alert-light border small mt-3 mb-0">
                            {{ __('Status') }} : <strong>{{ $e->statut->getLabel() }}</strong> — {{ __('Halaqa') }} : <strong>{{ $e->groupe?->nom_ar ?? __('Unassigned') }}</strong>.
                            {{ __('These items are managed through the workflow (testing and assignment).') }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-secondary" id="prevBtn" disabled>
            <i class="bi bi-chevron-right me-1"></i>{{ __('Previous') }}
        </button>
        <button type="button" class="btn btn-outline-primary" id="nextBtn">{{ __('Next') }}</button>
        <a href="{{ $e ? route('etudiants.show', $e) : route('etudiants.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
        <button type="submit" class="btn btn-primary ms-auto">
            <i class="bi bi-save me-1"></i>{{ $e ? __('Save changes') : __('Register student') }}
        </button>
    </div>
</form>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const tabs = document.querySelectorAll('#wizardTabs .nav-link');
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    let current = 0;

    function sync() {
        tabs.forEach((t, i) => t.classList.toggle('active', i === current));
        document.querySelectorAll('.tab-pane').forEach((p, i) => {
            p.classList.toggle('show', i === current);
            p.classList.toggle('active', i === current);
        });
        prevBtn.disabled = current === 0;
        nextBtn.style.display = current === tabs.length - 1 ? 'none' : '';
    }

    prevBtn.addEventListener('click', () => { if (current > 0) { current--; sync(); } });
    nextBtn.addEventListener('click', () => { if (current < tabs.length - 1) { current++; sync(); } });
});
</script>
@endpush
