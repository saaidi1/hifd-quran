<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Granularité d'un rapport de synthèse (تقرير دوري) généré
 * à partir des rapports journaliers : semaine, mois ou année civile.
 */
enum TypeRapportPeriodique: string implements HasLabel
{
    case HEBDOMADAIRE = 'hebdomadaire';
    case MENSUEL      = 'mensuel';
    case ANNUEL       = 'annuel';

    /** Libellé arabe du rapport affiché dans l'interface. */
    public function getLabel(): string
    {
        return match ($this) {
            self::HEBDOMADAIRE => 'تقرير أسبوعي',
            self::MENSUEL      => 'تقرير شهري',
            self::ANNUEL       => 'تقرير سنوي',
        };
    }
}
