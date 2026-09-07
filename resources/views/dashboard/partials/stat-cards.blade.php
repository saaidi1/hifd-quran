{{-- Cartes statistiques (variables $stats passées par le contrôleur, adaptées au rôle). --}}
<div class="row g-3 mb-4">
    @foreach ($stats as $stat)
        <div class="col-6 col-md-4 col-xl">
            <div class="stat-card p-3 d-flex gap-3 align-items-center">
                <div class="stat-icon {{ $stat['icon_bg'] }}">
                    <i class="bi {{ $stat['icon'] }}"></i>
                </div>
                <div class="min-w-0">
                    <div class="stat-value">{{ $stat['value'] }}</div>
                    <div class="stat-label">{{ $stat['label'] }}</div>
                    <div class="stat-description text-truncate">{{ $stat['description'] }}</div>
                </div>
            </div>
        </div>
    @endforeach
</div>