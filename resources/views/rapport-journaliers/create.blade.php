@extends('layouts.app')

@section('title', __('New daily report'))
@section('page-title', __('New daily report'))

@section('content')
    <form method="POST" action="{{ route('rapport-journaliers.store') }}" id="rapportForm">
        @csrf

        <div class="app-card mb-3">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Student') }} <span class="text-danger">*</span></label>
                        <select name="etudiant_id" class="form-select @error('etudiant_id') is-invalid @enderror" required>
                            <option value="">{{ __('Select student') }}</option>
                            @foreach ($etudiants as $e)
                                <option value="{{ $e->id }}" @selected(old('etudiant_id') == $e->id)>
                                    {{ $e->nom_complet_ar }} — {{ $e->groupe?->nom_ar }}
                                </option>
                            @endforeach
                        </select>
                        @error('etudiant_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        @error('date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">{{ __('Date') }} <span class="text-danger">*</span></label>
                        <input type="date" name="date" value="{{ old('date', $dateJour) }}" max="{{ $dateJour }}" class="form-control @error('date') is-invalid @enderror" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">{{ __('Attendance') }} <span class="text-danger">*</span></label>
                        <select name="presence" class="form-select">
                            @foreach ($presences as $p)
                                <option value="{{ $p->value }}" @selected(old('presence', 'present') == $p->value)>{{ $p->getLabel() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">{{ __('Arrival time') }}</label>
                        <input type="time" name="heure_arrivee" value="{{ old('heure_arrivee') }}" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">{{ __('Behavior') }}</label>
                        <select name="note_comportement" class="form-select @error('note_comportement') is-invalid @enderror">
                            <option value="">—</option>
                            @foreach ($niveaux as $n)
                                <option value="{{ $n->value }}" @selected(old('note_comportement') == $n->value)>{{ $n->getLabel() }}</option>
                            @endforeach
                        </select>
                        @error('note_comportement')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label">{{ __('General remarks') }}</label>
                        <textarea name="remarques" rows="2" class="form-control">{{ old('remarques') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="app-card mb-3">
            <div class="card-body">
                @include('rapport-journaliers._lines-editor', [
                    'sourates' => $sourates,
                    'types'    => $types,
                    'lignes'   => old('lignes', []),
                ])
            </div>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('rapport-journaliers.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
            <button type="submit" class="btn btn-primary ms-auto"><i class="bi bi-save me-1"></i>{{ __('Save report') }}</button>
        </div>
    </form>
@endsection
