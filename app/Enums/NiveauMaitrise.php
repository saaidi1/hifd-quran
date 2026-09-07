<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Niveau de maîtrise d'une tâche de mémorisation lors du تسميع
 * (récitation évaluée) : cinq paliers traduits en appréciation arabe,
 * dérivables d'une note sur /20 via depuisNote().
 */
enum NiveauMaitrise: string implements HasLabel, HasColor
{
    case EXCELLENT = 'excellent';
    case TRES_BIEN = 'tres_bien';
    case BIEN      = 'bien';
    case MOYEN     = 'moyen';
    case FAIBLE    = 'faible';

    /** Appréciation arabe du niveau de maîtrise. */
    public function getLabel(): string
    {
        return match ($this) {
            self::EXCELLENT => 'ممتاز',
            self::TRES_BIEN => 'جيد جدا',
            self::BIEN      => 'جيد',
            self::MOYEN     => 'متوسط',
            self::FAIBLE    => 'ضعيف',
        };
    }

    /** Du vert (maîtrise solide) au rouge (à retravailler). */
    public function getColor(): string
    {
        return match ($this) {
            self::EXCELLENT, self::TRES_BIEN => 'success',
            self::BIEN                       => 'info',
            self::MOYEN                      => 'warning',
            self::FAIBLE                     => 'danger',
        };
    }

    /** Convertit une note /20 en palier de maîtrise (ممتاز ≥ 18, جيد جدا ≥ 16…). */
    public static function depuisNote(float $note): self
    {
        return match (true) {
            $note >= 18 => self::EXCELLENT,
            $note >= 16 => self::TRES_BIEN,
            $note >= 14 => self::BIEN,
            $note >= 10 => self::MOYEN,
            default     => self::FAIBLE,
        };
    }
}
