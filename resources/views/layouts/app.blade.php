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
<body>
    @php $user = auth()->user(); @endphp

    <aside class="app-sidebar" id="appSidebar">
        <a href="{{ route('dashboard') }}" class="sidebar-brand">
            <i class="bi bi-journal-bookmark-fill"></i>
            <span>{{ __('Quran Memorization School') }}</span>
        </a>
        @include('layouts.partials.navigation')
    </aside>

    <div class="position-fixed top-0 start-0 w-100 h-100 bg-black bg-opacity-50 d-none" id="sidebarOverlay"></div>

    <main class="app-content">
        <nav class="app-topbar d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-outline-secondary d-lg-none" type="button" id="sidebarToggler" aria-label="{{ __('Menu') }}">
                    <i class="bi bi-list"></i>
                </button>
                <h1 class="h5 mb-0">@yield('page-title', __('Dashboard'))</h1>
            </div>

            <div class="d-flex align-items-center gap-2">
                <div class="dropdown">
                    <button class="btn user-menu-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="avatar-circle avatar-sm">{{ mb_substr($user->getFilamentName(), 0, 1) }}</span>
                        <span class="d-none d-sm-inline">{{ $user->getFilamentName() }}</span>
                        <span class="badge text-bg-light d-none d-md-inline">{{ $user->role->getLabel() }}</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <span class="dropdown-item-text text-muted">
                                <i class="bi bi-envelope me-1"></i>{{ $user->email }}
                            </span>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a href="{{ route('profile.edit') }}" class="dropdown-item">
                                <i class="bi bi-person me-1"></i>{{ __('Profile') }}
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('password.edit') }}" class="dropdown-item">
                                <i class="bi bi-key me-1"></i>{{ __('Change password') }}
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger">
                                    <i class="bi bi-box-arrow-left me-1"></i>{{ __('Sign out') }}
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <div class="container-fluid p-3 p-lg-4">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('Close') }}"></button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('Close') }}"></button>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $erreur)
                            <li>{{ $erreur }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('Close') }}"></button>
                </div>
            @endif

            @yield('content')
        </div>
    </main>

    @vite(['resources/js/app.js'])
    @stack('scripts')
</body>
</html>
