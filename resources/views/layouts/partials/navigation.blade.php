@php
    $user = auth()->user();
    $estProfesseur = $user->estProfesseur();
    $estGarde = $user->estGarde();
    $estDirecteur = $user->estDirecteur();
    $estSuperviseur = $user->estSuperviseur();
    $current = request()->route()?->getName();
@endphp

<nav class="nav flex-column sidebar-nav">
    <div class="sidebar-group-label">{{ __('Student affairs') }}</div>
    <a href="{{ route('etudiants.index') }}"
       class="nav-link {{ str_starts_with($current ?? '', 'etudiants.') ? 'active' : '' }}">
        <i class="bi bi-people"></i>
        {{ __('Students') }}
        @if ($enAttente = App\Models\Etudiant::enAttente()->count())
            <span class="badge text-bg-warning sidebar-badge">{{ $enAttente }}</span>
        @endif
    </a>
    <a href="{{ route('groupes.index') }}"
       class="nav-link {{ str_starts_with($current ?? '', 'groupes.') ? 'active' : '' }}">
        <i class="bi bi-diagram-3"></i>
        {{ __('Halaqas') }}
    </a>

    <div class="sidebar-group-label">{{ __('Memorization & follow-up') }}</div>
    @if (!$estDirecteur)
        <a href="{{ route('tasmi.index') }}"
           class="nav-link {{ str_starts_with($current ?? '', 'tasmi.') ? 'active' : '' }}">
            <i class="bi bi-mic"></i>
            {{ __('Halaqa recitation') }}
        </a>
    @endif
    <a href="{{ route('rapport-journaliers.index') }}"
       class="nav-link {{ str_starts_with($current ?? '', 'rapport-journaliers.') ? 'active' : '' }}">
        <i class="bi bi-journal-text"></i>
        {{ __('Daily reports') }}
    </a>

    @if ($estDirecteur || $estSuperviseur)
        <div class="sidebar-group-label">{{ __('Reports') }}</div>
        <a href="{{ route('rapports-periodiques.index') }}"
           class="nav-link {{ str_starts_with($current ?? '', 'rapports-periodiques.') ? 'active' : '' }}">
            <i class="bi bi-bar-chart"></i>
            {{ __('Weekly and monthly reports') }}
        </a>
    @endif

    @if ($estDirecteur)
        <div class="sidebar-group-label">{{ __('Administration') }}</div>
        <a href="{{ route('professeurs.index') }}"
           class="nav-link {{ str_starts_with($current ?? '', 'professeurs.') ? 'active' : '' }}">
            <i class="bi bi-person-workspace"></i>
            {{ __('Teachers and staff') }}
        </a>
    @endif

    @if ($estGarde)
        <div class="sidebar-group-label">{{ __('Administration') }}</div>
        <a href="{{ route('logements.index') }}"
           class="nav-link {{ str_starts_with($current ?? '', 'logements.') ? 'active' : '' }}">
            <i class="bi bi-house-door"></i>
            {{ __('Rooms and accommodation') }}
        </a>
    @endif
</nav>
