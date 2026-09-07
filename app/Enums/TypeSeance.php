<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Nature de la séance évaluée dans une ligne de rapport journalier.
 * Seules deux valeurs subsistent depuis la simplification de l'application :
 *   - HIFD_JADID (الحفظ الجديد) : nouvelle mémorisation ;
 *   - HIFD_QADIM (الحفظ القديم) : révision de la mémorisation ancienne,
 *     qui absorbe les anciens types « murajaa_qariba », « murajaa_baida »
 *     et « tilawa » désormais supprimés du code comme des données.
 */
enum TypeSeance: string implements HasLabel, HasColor
{
    case HIFD_JADID = 'hifd_jadid';
    case HIFD_QADIM = 'hifd_qadim';

    /** Libellé arabe affiché dans les formulaires et tableaux Filament. */
    public function getLabel(): string
    {
        return match ($this) {
            self::HIFD_JADID => 'الحفظ الجديد',
            self::HIFD_QADIM => 'الحفظ القديم',
        };
    }

    /** Couleur de badge : vert pour la mémorisation, ambre pour la révision. */
    public function getColor(): string
    {
        return match ($this) {
            self::HIFD_JADID => 'success',
            self::HIFD_QADIM => 'warning',
        };
    }
}
