<?php

namespace App\Policies;

use App\Models\Etudiant;
use App\Models\User;

/**
 * Autorisations sur le modèle Etudiant, alignées sur le workflow d'admission :
 *  - Garde (الحارس) + directeur : pré-inscription, édition, affectation, logistique.
 *  - Superviseur (المشرف) : évaluation (test) et validation/refus.
 *  - Professeur : consultation limitée aux étudiants de SES halaqas.
 *  - Directeur seul : suppression définitive.
 *
 * Appelée via authorize() / Gate dans les contrôleurs et les routes.
 */
class EtudiantPolicy
{
    /** Tout rôle authentifié peut lister ; le filtrage fin se fait par requête (scope) côté contrôleur. */
    public function viewAny(User $user): bool
    {
        return true; // tous les rôles voient une liste, filtrée par scope
    }

    /** Consultation d'une fiche : professeur limité à ses halaqas ; directeur, superviseur et garde voient tout. */
    public function view(User $user, Etudiant $etudiant): bool
    {
        // Le professeur ne voit que les étudiants de ses propres حلقات
        if ($user->estProfesseur()) {
            return $etudiant->groupe && $etudiant->groupe->professeur_id === $user->id;
        }

        return true;
    }

    /** Pré-inscription : الحارس العام (et le directeur). */
    /** Création d'un dossier (pré-inscription). */
    public function create(User $user): bool
    {
        return $user->estGarde() || $user->estDirecteur();
    }

    /** Modification du dossier : garde ou directeur uniquement. */
    public function update(User $user, Etudiant $etudiant): bool
    {
        return $user->estGarde() || $user->estDirecteur();
    }

    /** Test et validation / refus : المشرف على الأساتذة. */
    public function evaluer(User $user, Etudiant $etudiant): bool
    {
        return $user->estSuperviseur();
    }

    /** Affectation à une halaqa : garde/directeur, et uniquement si le dossier est validé (peutEtreAffecte). */
    public function affecter(User $user, Etudiant $etudiant): bool
    {
        return ($user->estGarde() || $user->estDirecteur()) && $etudiant->peutEtreAffecte();
    }

    /** Logistique (lit d'internat, place de prière) : garde ou directeur ; le lit/la place portent le matricule. */
    public function gererLogistique(User $user, Etudiant $etudiant): bool
    {
        return $user->estGarde() || $user->estDirecteur();
    }

    /** Suppression d'un dossier : directeur seul. */
    public function delete(User $user, Etudiant $etudiant): bool
    {
        return $user->estDirecteur();
    }
}
