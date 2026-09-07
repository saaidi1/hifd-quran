<?php

namespace App\Policies;

use App\Models\RapportJournalier;
use App\Models\User;

/**
 * Autorisations sur les rapports journaliers (تسميع) :
 *  - Le professeur saisit et modifie UNIQUEMENT ses propres rapports.
 *  - Directeur/superviseur/garde consultent tout ; seul le directeur supprime.
 */
class RapportJournalierPolicy
{
    /** Tout rôle authentifié peut lister (le contrôleur filtre ensuite par groupe pour les professeurs). */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /** Consultation d'un rapport : le professeur ne voit que les siens (professeur_id), les autres rôles voient tout. */
    public function view(User $user, RapportJournalier $rapport): bool
    {
        return $user->estProfesseur() ? $rapport->professeur_id === $user->id : true;
    }

    /** Seul le professeur saisit les rapports journaliers. */
    public function create(User $user): bool
    {
        return $user->estProfesseur();
    }

    /** Modification : professeur propriétaire du rapport uniquement. */
    public function update(User $user, RapportJournalier $rapport): bool
    {
        return $user->estProfesseur() && $rapport->professeur_id === $user->id;
    }

    /** Suppression : directeur seul (même le professeur auteur ne peut pas supprimer). */
    public function delete(User $user, RapportJournalier $rapport): bool
    {
        return $user->estDirecteur();
    }
}
