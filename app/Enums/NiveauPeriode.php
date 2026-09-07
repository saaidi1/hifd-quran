<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Niveau qualitatif à 3 niveaux pour les moyennes des rapports périodiques.
 * Traduit une note moyenne sur /20 en appréciation arabe lisible
 * (comportement, synthèse de période) au lieu d'un chiffre brut.
 */
enum NiveauPeriode: string implements HasLabel, HasColor
{
    case EXCELLENT = 'excellent';
    case MOYEN     = 'moyen';
    case FAIBLE    = 'faible';

    /** Appréciation arabe affichée sur la fiche de synthèse. */
    public function getLabel(): string
    {
        return match ($this) {
            self::EXCELLENT => 'ممتاز',
            self::MOYEN     => 'متوسط',
            self::FAIBLE    => 'ضعيف',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::EXCELLENT => 'success',
            self::MOYEN     => 'warning',
            self::FAIBLE    => 'danger',
        };
    }

    /**
     * Convertit une moyenne sur /20 en niveau qualitatif
     * (ممتاز ≥ 16, متوسط ≥ 12, sinon ضعيف) ; null si pas de note.
     */
    public static function depuisNote(?float $note): ?self
    {
        if ($note === null) {
            return null;
        }

        return match (true) {
            $note >= 16 => self::EXCELLENT,
            $note >= 12 => self::MOYEN,
            default     => self::FAIBLE,
        };
    }
}
