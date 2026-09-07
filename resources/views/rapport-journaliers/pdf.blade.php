<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>{{ __('Daily report') }} — {{ $rapport->etudiant?->nom_complet_ar }}</title>
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 12px; color: #111; margin: 0; }
        h1 { font-size: 18px; text-align: center; margin: 0 0 2px; }
        .sub { text-align: center; color: #555; font-size: 11px; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        th, td { border: 1px solid #999; padding: 4px 8px; font-size: 11px; text-align: right; }
        th { background: #ececec; }
        .hdr td { border: none; padding: 2px 0; }
        .hdr b { display: inline-block; min-width: 90px; }
        .center { text-align: center; }
        .footer { text-align: center; color: #777; font-size: 10px; margin-top: 12px; }
        .empty { color: #888; text-align: center; }
    </style>
</head>
<body>
    <h1>{{ config('app.name') }}</h1>
    <div class="sub">{{ __('Daily report') }}</div>

    <table class="hdr">
        <tr>
            <td class="center"><b>{{ __('Student') }}:</b> {{ $rapport->etudiant?->nom_complet_ar }}</td>
            <td class="center"><b>{{ __('Date') }}:</b> {{ $rapport->date->translatedFormat('l d/m/Y') }}</td>
            <td class="center"><b>{{ __('Halaqa') }}:</b> {{ $rapport->groupe?->nom_ar }}</td>
        </tr>
        <tr>
            <td class="center"><b>{{ __('Attendance') }}:</b> {{ $rapport->presence?->getLabel() }}</td>
            <td class="center"><b>{{ __('Behavior') }}:</b> {{ $rapport->note_comportement?->getLabel() ?? '—' }}</td>
            <td class="center"><b>{{ __('Overall average') }}:</b> {{ $rapport->note_globale ?? '—' }}</td>
        </tr>
    </table>

    @if ($rapport->remarques)
        <p><b>{{ __('Remarks') }}:</b> {{ $rapport->remarques }}</p>
    @endif

    <table>
        <tr>
            <th>{{ __('Type') }}</th>
            <th>{{ __('Read portion') }}</th>
            <th>{{ __('Pages') }}</th>
            <th>{{ __('Errors') }}</th>
            <th>{{ __('Hesitations') }}</th>
            <th>{{ __('Note') }}</th>
        </tr>
        @forelse ($rapport->lignes as $lg)
            <tr>
                <td>{{ $lg->type?->getLabel() }}</td>
                <td>{{ $lg->sourateDebut->numero }}:{{ $lg->ayah_debut }} → {{ $lg->sourateFin->numero }}:{{ $lg->ayah_fin }}</td>
                <td class="center">{{ $lg->nb_pages ?? '—' }}</td>
                <td class="center">{{ $lg->nb_erreurs }}</td>
                <td class="center">{{ $lg->nb_hesitations }}</td>
                <td class="center">{{ $lg->note ?? '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="6" class="empty">{{ __('No reading details.') }}</td></tr>
        @endforelse
    </table>

    <div class="footer">{{ __('Teacher') }}: {{ $rapport->professeur?->nom_ar ?? '—' }} — {{ now()->translatedFormat('d/m/Y H:i') }}</div>
</body>
</html>