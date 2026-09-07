@extends('layouts.guest')

@section('title', __('Sign in'))

@section('content')
    <form method="POST" action="{{ route('login') }}" novalidate>
        @csrf

        <div class="mb-3">
            <label for="email" class="form-label">{{ __('Email') }}</label>
            <input type="email" class="form-control @error('email') is-invalid @enderror"
                   id="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">{{ __('Password') }}</label>
            <input type="password" class="form-control @error('password') is-invalid @enderror"
                   id="password" name="password" required autocomplete="current-password">
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="remember" id="remember">
            <label class="form-check-label" for="remember">{{ __('Remember me') }}</label>
        </div>

        <button type="submit" class="btn btn-primary w-100">
            <i class="bi bi-box-arrow-in-left me-1"></i>
            {{ __('Sign in') }}
        </button>
    </form>

    <div class="text-center mt-3">
        <a href="{{ route('password.request') }}" class="small">
            {{ __('Forgot your password?') }}
        </a>
    </div>
@endsection
