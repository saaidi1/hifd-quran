@php
    $l = $l ?? [];
    $erreur = $errors->has("lignes.$i.type") ? ' is-invalid' : '';
@endphp

<div class="ligne-range border rounded-3 p-2 mb-2 bg-light-subtle">
    <div class="row g-2 align-items-center">
        <div class="col-md-2">
            <select name="lignes[{{ $i }}][type]" class="form-select form-select-sm @error('lignes.' . $i . '.type') is-invalid @enderror">
                <option value="">— {{ __('Type') }} —</option>
                @foreach ($types as $t)
                    <option value="{{ $t->value }}" @selected(($l['type'] ?? '') == $t->value)>{{ $t->getLabel() }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <select name="lignes[{{ $i }}][sourate_debut_id]" class="form-select form-select-sm">
                <option value="">{{ __('Start surah') }}</option>
                @foreach ($sourates as $s)
                    <option value="{{ $s->id }}" @selected(($l['sourate_debut_id'] ?? '') == $s->id)>{{ $s->numero }}. {{ $s->nom_ar }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-1">
            <input type="text" name="lignes[{{ $i }}][ayah_debut]" value="{{ $l['ayah_debut'] ?? '' }}" class="form-control form-control-sm" placeholder="{{ __('Ayah') }}">
        </div>
        <div class="col-md-2">
            <select name="lignes[{{ $i }}][sourate_fin_id]" class="form-select form-select-sm">
                <option value="">{{ __('End surah') }}</option>
                @foreach ($sourates as $s)
                    <option value="{{ $s->id }}" @selected(($l['sourate_fin_id'] ?? '') == $s->id)>{{ $s->numero }}. {{ $s->nom_ar }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-1">
            <input type="text" name="lignes[{{ $i }}][ayah_fin]" value="{{ $l['ayah_fin'] ?? '' }}" class="form-control form-control-sm" placeholder="{{ __('Ayah') }}">
        </div>
        <div class="col-md-1">
            <input type="number" name="lignes[{{ $i }}][nb_pages]" step="0.25" min="0" value="{{ $l['nb_pages'] ?? '' }}" class="form-control form-control-sm" placeholder="{{ __('Pages') }}">
        </div>
        <div class="col-md-2 d-flex gap-1">
            <input type="number" name="lignes[{{ $i }}][nb_erreurs]" min="0" value="{{ $l['nb_erreurs'] ?? '' }}" class="form-control form-control-sm" placeholder="{{ __('Errors') }}">
            <input type="number" name="lignes[{{ $i }}][nb_hesitations]" min="0" value="{{ $l['nb_hesitations'] ?? '' }}" class="form-control form-control-sm" placeholder="{{ __('Hesitations') }}">
        </div>
        <div class="col-md-1 text-end">
            <button type="button" class="btn btn-sm btn-outline-danger removeLigne" title="{{ __('Delete line') }}">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="col-12">
            <input type="text" name="lignes[{{ $i }}][observation]" value="{{ $l['observation'] ?? '' }}" class="form-control form-control-sm" placeholder="{{ __('Note about the reading (optional)') }}">
        </div>
    </div>
</div>
