@extends('layouts.app')

@section('title', __('Change password'))
@section('page-title', __('Change password'))

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <i class="bi bi-key me-1"></i>{{ __('Change password') }}
                </div>

                <div class="card-body">
                    <form method="POST" action="{{ route('password.update') }}" novalidate>
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="current_password" class="form-label">{{ __('Current password') }}</label>
                            <input type="password" id="current_password" name="current_password"
                                   class="form-control @error('current_password') is-invalid @enderror"
                                   required autocomplete="current-password">
                            @error('current_password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">{{ __('New password') }}</label>
                            <input type="password" id="password" name="password"
                                   class="form-control @error('password') is-invalid @enderror"
                                   required minlength="8" autocomplete="new-password">
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">{{ __('Confirm new password') }}</label>
                            <input type="password" id="password_confirmation" name="password_confirmation"
                                   class="form-control" required autocomplete="new-password">
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-check-lg me-1"></i>{{ __('Update password') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
