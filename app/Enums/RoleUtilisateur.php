<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Rôles métier de l'école coranique (صلاحيات المستخدمين).
 * Attention : le superviseur (SUPERVISEUR) est AUSSI professeur —
 * il dispose des mêmes capacités de saisie qu'un professeur,
 * en plus de la gestion des tests et validations d'inscription.
 * Le garde général (الحارس العام) gère les pré-inscriptions et l'internat.
 */
enum RoleUtilisateur: string implements HasLabel, HasColor
{
    case DIRECTEUR   = 'directeur';      // المدير
    case SUPERVISEUR = 'superviseur';    // المشرف على الأساتذة
    case GARDE       = 'garde_general';  // الحارس العام
    case PROFESSEUR  = 'professeur';     // الأستاذ

    /** Libellé arabe du rôle affiché dans l'interface. */
    public function getLabel(): string
    {
        return match ($this) {
            self::DIRECTEUR   => 'المدير',
            self::SUPERVISEUR => 'المشرف على الأساتذة',
            self::GARDE       => 'الحارس العام',
            self::PROFESSEUR  => 'الأستاذ',
        };
    }

    /** Libellé français (journaux, notifications, exports). */
    public function libelle(): string
    {
        return match ($this) {
            self::DIRECTEUR   => 'Directeur',
            self::SUPERVISEUR => 'Superviseur des professeurs',
            self::GARDE       => 'Garde général',
            self::PROFESSEUR  => 'Professeur',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::DIRECTEUR   => 'danger',
            self::SUPERVISEUR => 'warning',
            self::GARDE       => 'info',
            self::PROFESSEUR  => 'success',
        };
    }
}
