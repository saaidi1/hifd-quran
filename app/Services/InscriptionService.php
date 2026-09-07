<?php

namespace App\Services;

use App\Enums\StatutInscription;
use App\Models\Affectation;
use App\Models\Etudiant;
use App\Models\EvaluationInscription;
use App\Models\Groupe;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Service métier du parcours d'inscription d'un étudiant (تسجيل طالب جديد).
 *
 * Workflow :
 *   الحارس العام  -> pré-inscription
 *   المشرف على الأساتذة -> test + validation / refus
 *   الحارس العام (ou le Directeur) -> affectation à un groupe (uniquement si validé)
 */
class InscriptionService
{
    /** Étape 1 : pré-inscription par le garde général. */
    public function preinscrire(array $donnees, User $garde): Etudiant
    {
        return DB::transaction(function () use ($donnees, $garde) {
            return Etudiant::create($donnees + [
                'matricule'           => $this->genererMatricule(),
                'statut'              => StatutInscription::PREINSCRIT,
                'preinscrit_par'      => $garde->id,
                'date_preinscription' => now()->toDateString(),
            ]);
        });
    }

    /** Étape 2 : test et décision du superviseur — met à jour le statut (validé/refusé/ajourné) et le niveau de hifd initial. */
    public function evaluer(Etudiant $etudiant, array $donnees, User $superviseur): EvaluationInscription
    {
        return DB::transaction(function () use ($etudiant, $donnees, $superviseur) {
            $evaluation = $etudiant->evaluations()->create($donnees + [
                'superviseur_id' => $superviseur->id,
                'date_test'      => $donnees['date_test'] ?? now()->toDateString(),
            ]);

            $etudiant->update([
                'statut' => match ($donnees['decision']) {
                    'valide'  => StatutInscription::VALIDE,
                    'refuse'  => StatutInscription::REFUSE,
                    default   => StatutInscription::AJOURNE,
                },
                'valide_par'        => $superviseur->id,
                'date_validation'   => now()->toDateString(),
                'motif_refus'       => $donnees['decision'] === 'refuse' ? ($donnees['motif'] ?? null) : null,
                'hifd_initial_hizb' => $donnees['hizb_maitrise'] ?? $etudiant->hifd_initial_hizb ?? 0,
            ]);

            return $evaluation;
        });
    }

    /** Étape 3 : affectation à un groupe — refusée si l'inscription n'est pas validée ; désactive l'affectation précédente. */
    public function affecter(Etudiant $etudiant, Groupe $groupe, User $auteur, ?string $motif = null): Affectation
    {
        if (! $etudiant->peutEtreAffecte()) {
            throw ValidationException::withMessages([
                'etudiant' => "L'affectation est impossible : l'inscription doit d'abord être validée par le superviseur (المشرف على الأساتذة).",
            ]);
        }

        if ($groupe->estComplet()) {
            throw ValidationException::withMessages([
                'groupe' => "Le groupe « {$groupe->nom} » a atteint sa capacité maximale.",
            ]);
        }

        return DB::transaction(function () use ($etudiant, $groupe, $auteur, $motif) {
            $etudiant->affectations()->where('actif', true)->update([
                'actif'    => false,
                'date_fin' => now()->toDateString(),
            ]);

            $affectation = $etudiant->affectations()->create([
                'groupe_id'   => $groupe->id,
                'affecte_par' => $auteur->id,
                'date_debut'  => now()->toDateString(),
                'motif'       => $motif,
                'actif'       => true,
            ]);

            $etudiant->update(['groupe_id' => $groupe->id]);

            return $affectation;
        });
    }

    /** Génère le matricule séquentiel à 4 chiffres du nouvel étudiant. */
    private function genererMatricule(): string
    {
        // Numéro d'inscription global unique : le même numéro servira
        // pour le lit (رقم السرير) et pour la place de prière (رقم المكان).
        $numero = (int) Etudiant::max('id') + 1;

        return sprintf('%04d', $numero);
    }
}
