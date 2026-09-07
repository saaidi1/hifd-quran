@extends('layouts.guest')

@section('title', __('Reset your password'))

@section('content')
    <p class="text-muted small mb-3">
        {{ __('Choose a new password for your account.') }}
    </p>

    <form method="POST" action="{{ route('password.store') }}" novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div class="mb-3">
            <label for="email" class="form-label">{{ __('Email') }}</label>
            <input type="email" class="form-control @error('email') is-invalid @enderror"
                   id="email" name="email" value="{{ old('email', $email) }}" required autocomplete="username">
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">{{ __('New password') }}</label>
            <input type="password" class="form-control @error('password') is-invalid @enderror"
                   id="password" name="password" required minlength="8" autocomplete="new-password">
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="password_confirmation" class="form-label">{{ __('Confirm new password') }}</label>
            <input type="password" class="form-control"
                   id="password_confirmation" name="password_confirmation" required autocomplete="new-password">
        </div>

        <button type="submit" class="btn btn-primary w-100">
            <i class="bi bi-key me-1"></i>
            {{ __('Set new password') }}
        </button>
    </form>

    <div class="text-center mt-3">
        <a href="{{ route('login') }}" class="small">{{ __('Back to sign in') }}</a>
    </div>
@endsection
