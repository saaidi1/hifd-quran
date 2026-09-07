<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Niveau qualitatif de la moyenne générale d'un rapport journalier
 * (جيد / متوسط / ضعيف) : convertit une note sur /20 en appréciation
 * arabe via la méthode depuisNote().
 */
enum NiveauMoyenne: string implements HasLabel, HasColor
{
    case BON   = 'bon';
    case MOYEN = 'moyen';
    case FAIBLE = 'faible';

    /** Appréciation arabe affichée en badge sur le rapport. */
    public function getLabel(): string
    {
        return match ($this) {
            self::BON    => 'جيد',
            self::MOYEN  => 'متوسط',
            self::FAIBLE => 'ضعيف',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::BON    => 'success',
            self::MOYEN  => 'info',
            self::FAIBLE => 'danger',
        };
    }

    /** Niveau qualitatif d'une moyenne sur /20 (جيد ≥ 16, متوسط ≥ 12). */
    public static function depuisNote(?float $note): ?self
    {
        if ($note === null) {
            return null;
        }

        return match (true) {
            $note >= 16 => self::BON,
            $note >= 12 => self::MOYEN,
            default     => self::FAIBLE,
        };
    }
}
