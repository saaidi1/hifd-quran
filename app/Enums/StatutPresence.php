<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

/**
 * Assiduité de l'étudiant lors d'une séance (الحضور والغياب) :
 * présent, absent, excusé (absence justifiée comptée à part)
 * ou en retard.
 */
enum StatutPresence: string implements HasLabel, HasColor, HasIcon
{
    case PRESENT = 'present';
    case ABSENT  = 'absent';
    case RETARD  = 'retard';
    case EXCUSE  = 'excuse';

    /** Libellé arabe affiché dans les rapports journaliers. */
    public function getLabel(): string
    {
        return match ($this) {
            self::PRESENT => 'حاضر',
            self::ABSENT  => 'غائب',
            self::RETARD  => 'متأخر',
            self::EXCUSE  => 'غياب بعذر',
        };
    }

    /** Couleur du badge de présence dans les tableaux Filament. */
    public function getColor(): string
    {
        return match ($this) {
            self::PRESENT => 'success',
            self::ABSENT  => 'danger',
            self::RETARD  => 'warning',
            self::EXCUSE  => 'info',
        };
    }

    /** Icône associée à chaque état de présence. */
    public function getIcon(): string
    {
        return match ($this) {
            self::PRESENT => 'heroicon-o-check-circle',
            self::ABSENT  => 'heroicon-o-x-circle',
            self::RETARD  => 'heroicon-o-clock',
            self::EXCUSE  => 'heroicon-o-document-text',
        };
    }
}
