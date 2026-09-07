<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Cycle de vie d'une inscription (دورة التسجيل) :
 * pré-inscription par le garde → test du superviseur
 * → validé (affectable à un groupe) / refusé / ajourné,
 * avec abandon possible en cours de scolarité.
 */
enum StatutInscription: string implements HasLabel, HasColor
{
    case PREINSCRIT = 'preinscrit';
    case EN_TEST    = 'en_test';
    case VALIDE     = 'valide';
    case REFUSE     = 'refuse';
    case AJOURNE    = 'ajourne';
    case ABANDON    = 'abandon';

    /** Libellé arabe de l'état d'inscription affiché en badge. */
    public function getLabel(): string
    {
        return match ($this) {
            self::PREINSCRIT => 'تسجيل مبدئي',
            self::EN_TEST    => 'في طور الاختبار',
            self::VALIDE     => 'مقبول',
            self::REFUSE     => 'مرفوض',
            self::AJOURNE    => 'مؤجل',
            self::ABANDON    => 'منقطع',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PREINSCRIT => 'warning',
            self::EN_TEST    => 'info',
            self::VALIDE     => 'success',
            self::REFUSE     => 'danger',
            self::AJOURNE    => 'gray',
            self::ABANDON    => 'gray',
        };
    }

    /** Seul un étudiant validé peut être rattaché à un groupe. */
    public function peutEtreAffecte(): bool
    {
        return $this === self::VALIDE;
    }
}
