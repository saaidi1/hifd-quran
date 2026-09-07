@extends('layouts.app')

@section('title', __('Edit halaqa'))
@section('page-title', __('Edit halaqa'))

@section('content')
    @php $g = $groupe; @endphp

    <form method="POST" action="{{ route('groupes.update', $g) }}">
        @csrf
        @method('PUT')

        <div class="app-card">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Halaqa name') }} <span class="text-danger">*</span></label>
                        <input type="text" name="nom_ar" value="{{ old('nom_ar', $g?->nom_ar) }}" class="form-control @error('nom_ar') is-invalid @enderror" required>
                        @error('nom_ar')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Name (Latin)') }}</label>
                        <input type="text" name="nom" value="{{ old('nom', $g?->nom) }}" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Supervising teacher') }}</label>
                        <select name="professeur_id" class="form-select">
                            <option value="">—</option>
                            @foreach ($professeurs as $p)
                                    <option value="{{ $p->id }}" @selected(old('professeur_id', $g?->professeur_id) == $p->id)>{{ $p->nom_ar ?: $p->nom }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Level') }}</label>
                        <select name="niveau" class="form-select">
                            <option value="">—</option>
                            @foreach (['mubtadi' => __('Beginner'), 'moutawassit' => __('Intermediate'), 'moutaqaddim' => __('Advanced'), 'khatma' => __('Complete recitation')] as $val => $lib)
                                <option value="{{ $val }}" @selected(old('niveau', $g?->niveau) === $val)>{{ $lib }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Hall') }}</label>
                        <input type="text" name="salle" value="{{ old('salle', $g?->salle) }}" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('Schedule') }}</label>
                        <div class="row g-2">
                            <div class="col">
                                <label class="form-label small text-muted mb-1">{{ __('From') }}</label>
                                <input type="time" name="horaire_debut" value="{{ old('horaire_debut', $g?->horaire_debut) }}" class="form-control">
                            </div>
                            <div class="col">
                                <label class="form-label small text-muted mb-1">{{ __('To') }}</label>
                                <input type="time" name="horaire_fin" value="{{ old('horaire_fin', $g?->horaire_fin) }}" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Capacity') }} <span class="text-danger">*</span></label>
                        <input type="number" name="capacite" min="1" value="{{ old('capacite', $g?->capacite) }}" class="form-control @error('capacite') is-invalid @enderror" required>
                        @error('capacite')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('School year') }}</label>
                        <input type="text" name="annee_scolaire" value="{{ old('annee_scolaire', $g?->annee_scolaire) }}" class="form-control">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="actif" value="1" id="actif" @checked(old('actif', $g?->actif))>
                            <label class="form-check-label" for="actif">{{ __('Active') }}</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer d-flex gap-2">
                <a href="{{ route('groupes.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                <button type="submit" class="btn btn-primary ms-auto"><i class="bi bi-save me-1"></i>{{ __('Save') }}</button>
            </div>
        </div>
    </form>
@endsection
