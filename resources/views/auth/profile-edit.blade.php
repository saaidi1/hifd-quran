@extends('layouts.app')

@section('title', __('Profile'))
@section('page-title', __('Profile'))

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <i class="bi bi-person-circle me-1"></i>{{ __('Personal information') }}
                </div>

                <div class="card-body">
                    <form method="POST" action="{{ route('profile.update') }}" novalidate>
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nom" class="form-label">{{ __('Last name (Latin)') }}</label>
                                <input type="text" id="nom" name="nom"
                                       class="form-control @error('nom') is-invalid @enderror"
                                       value="{{ old('nom', $user->nom) }}" required>
                                @error('nom')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="prenom" class="form-label">{{ __('First name (Latin)') }}</label>
                                <input type="text" id="prenom" name="prenom"
                                       class="form-control @error('prenom') is-invalid @enderror"
                                       value="{{ old('prenom', $user->prenom) }}" required>
                                @error('prenom')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="nom_ar" class="form-label">{{ __('Full name in Arabic') }}</label>
                                <input type="text" id="nom_ar" name="nom_ar" dir="rtl"
                                       class="form-control @error('nom_ar') is-invalid @enderror"
                                       value="{{ old('nom_ar', $user->nom_ar) }}">
                                @error('nom_ar')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">{{ __('Email') }}</label>
                                <input type="email" id="email" name="email"
                                       class="form-control @error('email') is-invalid @enderror"
                                       value="{{ old('email', $user->email) }}" required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="telephone" class="form-label">{{ __('Phone') }}</label>
                                <input type="text" id="telephone" name="telephone"
                                       class="form-control @error('telephone') is-invalid @enderror"
                                       value="{{ old('telephone', $user->telephone) }}">
                                @error('telephone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="specialite" class="form-label">{{ __('Specialty') }}</label>
                                <input type="text" id="specialite" name="specialite"
                                       class="form-control @error('specialite') is-invalid @enderror"
                                       value="{{ old('specialite', $user->specialite) }}">
                                @error('specialite')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label d-block text-muted">{{ __('Role') }}</label>
                                <span class="badge text-bg-secondary">{{ $user->role->getLabel() }}</span>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i>{{ __('Save changes') }}
                        </button>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <i class="bi bi-key me-1"></i>{{ __('Password') }}
                    </div>
                    <a href="{{ route('password.edit') }}" class="btn btn-outline-primary btn-sm">
                        {{ __('Change password') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
