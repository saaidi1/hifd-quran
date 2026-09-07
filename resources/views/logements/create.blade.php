@extends('layouts.app')

@section('title', isset($logement) ? __('Edit room') : __('New room'))
@section('page-title', isset($logement) ? __('Edit room') : __('New room'))

@section('content')
    @php $c = $logement ?? null; @endphp

    <form method="POST" action="{{ $c ? route('logements.update', $c) : route('logements.store') }}">
        @csrf
        @if ($c) @method('PUT') @endif

        <div class="app-card">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Room number') }} <span class="text-danger">*</span></label>
                        <input type="text" name="numero" value="{{ old('numero', $c?->numero) }}" class="form-control @error('numero') is-invalid @enderror" required>
                        @error('numero')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Building') }}</label>
                        <input type="text" name="batiment" value="{{ old('batiment', $c?->batiment) }}" class="form-control" placeholder="{{ __('Main building') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Floor') }}</label>
                        <input type="text" name="etage" value="{{ old('etage', $c?->etage) }}" class="form-control" placeholder="{{ __('First') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Number of beds') }} <span class="text-danger">*</span></label>
                        <input type="number" name="capacite" min="1" value="{{ old('capacite', $c?->capacite ?? 10) }}" class="form-control @error('capacite') is-invalid @enderror" required>
                        @error('capacite')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Room supervisor') }}</label>
                        <select name="responsable_id" class="form-select">
                            <option value="">—</option>
                            @foreach ($responsables as $r)
                                <option value="{{ $r->id }}" @selected(old('responsable_id', $c?->responsable_id) == $r->id)>{{ $r->nom_ar }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="actif" value="1" id="actif" @checked(old('actif', $c?->actif ?? true))>
                            <label class="form-check-label" for="actif">{{ __('Available for accommodation') }}</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer d-flex gap-2">
                <a href="{{ route('logements.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                <button type="submit" class="btn btn-primary ms-auto"><i class="bi bi-save me-1"></i>{{ __('Save') }}</button>
            </div>
        </div>
    </form>
@endsection
