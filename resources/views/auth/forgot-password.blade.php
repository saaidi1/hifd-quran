@extends('layouts.guest')

@section('title', __('Forgot your password?'))

@section('content')
    <p class="text-muted small mb-3">
        {{ __('Enter your email address and we will send you a password reset link.') }}
    </p>

    <form method="POST" action="{{ route('password.email') }}" novalidate>
        @csrf

        <div class="mb-3">
            <label for="email" class="form-label">{{ __('Email') }}</label>
            <input type="email" class="form-control @error('email') is-invalid @enderror"
                   id="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit" class="btn btn-primary w-100">
            <i class="bi bi-envelope me-1"></i>
            {{ __('Send password reset link') }}
        </button>
    </form>

    <div class="text-center mt-3">
        <a href="{{ route('login') }}" class="small">{{ __('Back to sign in') }}</a>
    </div>
@endsection
