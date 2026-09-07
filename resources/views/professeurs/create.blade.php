@extends('layouts.app')

@section('title', isset($professeur) ? __('Edit account') : __('New account'))
@section('page-title', isset($professeur) ? __('Edit account') : __('New account'))

@section('content')
    @php $p = $professeur ?? null; @endphp

    <form method="POST" action="{{ $p ? route('professeurs.update', $p) : route('professeurs.store') }}">
        @csrf
        @if ($p) @method('PUT') @endif

        <div class="app-card">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Full name in Arabic') }} <span class="text-danger">*</span></label>
                        <input type="text" name="nom_ar" value="{{ old('nom_ar', $p?->nom_ar) }}" class="form-control @error('nom_ar') is-invalid @enderror" required>
                        @error('nom_ar')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Role') }} <span class="text-danger">*</span></label>
                        <select name="role" class="form-select @error('role') is-invalid @enderror">
                            @foreach (\App\Enums\RoleUtilisateur::cases() as $r)
                                <option value="{{ $r->value }}" @selected(old('role', $p?->role?->value) === $r->value)>{{ $r->getLabel() }}</option>
                            @endforeach
                        </select>
                        @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Email') }} <span class="text-danger">*</span></label>
                        <input type="email" name="email" value="{{ old('email', $p?->email) }}" dir="ltr" class="form-control @error('email') is-invalid @enderror" required>
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('First name (Latin)') }} <span class="text-danger">*</span></label>
                        <input type="text" name="prenom" value="{{ old('prenom', $p?->prenom) }}" class="form-control @error('prenom') is-invalid @enderror" required>
                        @error('prenom')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Last name (Latin)') }} <span class="text-danger">*</span></label>
                        <input type="text" name="nom" value="{{ old('nom', $p?->nom) }}" class="form-control @error('nom') is-invalid @enderror" required>
                        @error('nom')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Phone') }}</label>
                        <input type="tel" name="telephone" value="{{ old('telephone', $p?->telephone) }}" dir="ltr" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('Specialty') }}</label>
                        <input type="text" name="specialite" value="{{ old('specialite', $p?->specialite) }}" class="form-control" placeholder="{{ __('Warsh narration from Nafi') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ $p ? __('Password (leave blank to keep it)') : __('Password') }} <span class="text-danger">{{ $p ? '' : '*' }}</span></label>
                        <input type="password" name="password" minlength="8" class="form-control @error('password') is-invalid @enderror" {{ $p ? '' : 'required' }}>
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="actif" value="1" id="actif" @checked(old('actif', $p?->actif ?? true))>
                            <label class="form-check-label" for="actif">{{ __('Active account') }}</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer d-flex gap-2">
                <a href="{{ route('professeurs.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                <button type="submit" class="btn btn-primary ms-auto"><i class="bi bi-save me-1"></i>{{ __('Save') }}</button>
            </div>
        </div>
    </form>
@endsection
