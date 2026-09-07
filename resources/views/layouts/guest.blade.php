<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('Quran Memorization School'))</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Kufi+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
</head>
<body class="auth-page">
    <div class="auth-card">
        <div class="card-body p-4 p-md-5">
            <div class="text-center mb-4">
                <a href="{{ url('/') }}" class="auth-brand fs-4 d-inline-flex align-items-center gap-2">
                    <i class="bi bi-journal-bookmark-fill"></i>
                    {{ __('Quran Memorization School') }}
                </a>
            </div>

            @if (session('success'))
                <div class="alert alert-success" role="alert">{{ session('success') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger" role="alert">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $erreur)
                            <li>{{ $erreur }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </div>
    </div>

    @vite(['resources/js/app.js'])
</body>
</html>
