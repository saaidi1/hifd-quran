@extends('layouts.app')

@section('title', __('Edit daily report'))
@section('page-title', __('Edit daily report'))

@section('content')
    <form method="POST" action="{{ route('rapport-journaliers.update', $rapport) }}">
        @csrf
        @method('PUT')

        <div class="app-card mb-3">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Student') }}</label>
                        <input type="text" class="form-control" value="{{ $rapport->etudiant->nom_complet_ar }}" disabled>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">{{ __('Date') }}</label>
                        <input type="text" class="form-control" value="{{ $rapport->date->translatedFormat('d/m/Y') }}" disabled>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">{{ __('Attendance') }}</label>
                        <select name="presence" class="form-select">
                            @foreach ($presences as $p)
                                <option value="{{ $p->value }}" @selected($rapport->presence == $p)>{{ $p->getLabel() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">{{ __('Arrival time') }}</label>
                        <input type="time" name="heure_arrivee" value="{{ old('heure_arrivee', $rapport->heure_arrivee) }}" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">{{ __('Behavior') }}</label>
                        <select name="note_comportement" class="form-select">
                            <option value="">—</option>
                            @foreach ($niveaux as $n)
                                <option value="{{ $n->value }}" @selected(old('note_comportement', $rapport->note_comportement?->value) == $n->value)>{{ $n->getLabel() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">{{ __('General remarks') }}</label>
                        <textarea name="remarques" rows="2" class="form-control">{{ old('remarques', $rapport->remarques) }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="app-card mb-3">
            <div class="card-body">
                @include('rapport-journaliers._lines-editor', [
                    'sourates' => $sourates,
                    'types'    => $types,
                    'lignes'   => old('lignes', $rapport->lignes->map(fn ($l) => $l->only([
                        'type', 'sourate_debut_id', 'ayah_debut', 'sourate_fin_id',
                        'ayah_fin', 'nb_pages', 'note', 'nb_erreurs', 'nb_hesitations', 'observation',
                    ]))->all()),
                ])
            </div>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('rapport-journaliers.show', $rapport) }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
            <button type="submit" class="btn btn-primary ms-auto"><i class="bi bi-save me-1"></i>{{ __('Save changes') }}</button>
        </div>
    </form>
@endsection
