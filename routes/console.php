<?php

use App\Enums\TypeRapportPeriodique;
use App\Services\RapportPeriodiqueService;
use Illuminate\Support\Facades\Schedule;

// Génération automatique des synthèses
Schedule::call(fn () => app(RapportPeriodiqueService::class)->genererPourTous(TypeRapportPeriodique::HEBDOMADAIRE))
    ->weeklyOn(5, '20:00');   // chaque vendredi soir

Schedule::call(fn () => app(RapportPeriodiqueService::class)->genererPourTous(TypeRapportPeriodique::MENSUEL))
    ->monthlyOn(28, '21:00');
