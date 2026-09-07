<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Échelle qualitative du comportement (سلوك) : remplace la note /20.
 * Chaque niveau porte une appréciation arabe (excellent, bon, à améliorer,
 * faible) et une valeur numérique d'équivalence pour les moyennes périodiques.
 */
enum NiveauComportement: string implements HasLabel, HasColor
{
    case EXCELLENT    = 'excellent';
    case BON          = 'bon';
    case AMELIORATION = 'amelioration';
    case FAIBLE       = 'faible';

    /** Appréciation arabe de conduite affichée sur le rapport journalier. */
    public function getLabel(): string
    {
        return match ($this) {
            self::EXCELLENT    => 'سلوك ممتاز',
            self::BON          => 'سلوك جيد',
            self::AMELIORATION => 'تحسن ملحوظ',
            self::FAIBLE       => 'سلوك ضعيف',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::EXCELLENT => 'success',
            self::BON       => 'info',
            self::AMELIORATION => 'warning',
            self::FAIBLE    => 'danger',
        };
    }

    /** Équivalence numérique pour le calcul de la moyenne périodique. */
    public function valeur(): int
    {
        return match ($this) {
            self::EXCELLENT    => 20,
            self::BON          => 15,
            self::AMELIORATION => 12,
            self::FAIBLE       => 5,
        };
    }

    /** Conversion d'une ancienne note /20 vers le niveau le plus proche. */
    public static function depuisNote(float $note): self
    {
        return match (true) {
            $note >= 17.5 => self::EXCELLENT,
            $note >= 14   => self::BON,
            $note >= 10   => self::AMELIORATION,
            default       => self::FAIBLE,
        };
    }
}
