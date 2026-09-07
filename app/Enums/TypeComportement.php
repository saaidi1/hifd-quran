<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Sens d'un point de conduite relevé dans le rapport journalier (السلوك) :
 * fait positif valorisé, ou infraction / mauvaise conduite signalée.
 */
enum TypeComportement: string implements HasLabel, HasColor
{
    case POSITIF = 'positif';
    case NEGATIF = 'negatif';

    /** Libellé arabe : bonne action ou مخالفة (infraction). */
    public function getLabel(): string
    {
        return $this === self::POSITIF ? 'سلوك إيجابي' : 'مخالفة';
    }

    /** Vert pour un fait positif, rouge pour une infraction. */
    public function getColor(): string
    {
        return $this === self::POSITIF ? 'success' : 'danger';
    }
}
