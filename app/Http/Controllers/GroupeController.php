<?php

namespace App\Http\Controllers;

use App\Enums\RoleUtilisateur;
use App\Models\Groupe;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CRUD des halaqas (groupes, المجموعات).
 *
 * Périmètre :
 *  - Consultation : tous les rôles, mais le professeur ne voit que SES groupes.
 *  - Création/édition/suppression : directeur, superviseur, garde et professeur
 *    (le professeur étant limité à ses propres groupes — voir verifierAcces()).
 */
class GroupeController extends Controller
{
    /** Liste des groupes avec effectifs et professeur. Un professeur ne reçoit que ses propres groupes. */
    public function index(): View
    {
        $query = Groupe::query()->withCount('etudiants')->with('professeur');

        if (auth()->user()->estProfesseur()) {
            $query->where('professeur_id', auth()->id());
        }

        $groupes = $query->orderBy('nom_ar')->get();

        return view('groupes.index', ['groupes' => $groupes]);
    }

    /** Formulaire de création d'une halaqa avec la liste des encadrants actifs. Rôles autorisés : directeur, professeur, superviseur, garde. */
    public function create(): View
    {
        $this->verifierGestion();

        return view('groupes.create', ['professeurs' => $this->professeursActifs()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->verifierGestion();

        $donnees = $this->valider($request);

        // Sécurité : un professeur est toujours le responsable de son propre groupe.
        if (auth()->user()->estProfesseur()) {
            $donnees['professeur_id'] = auth()->id();
        }

        $donnees['nom'] = $donnees['nom'] ?? $donnees['nom_ar'];

        Groupe::create($donnees);

        return redirect()->route('groupes.index')->with('success', __('Halaqa added successfully.'));
    }

    /** Formulaire d'édition d'une halaqa. Accès restreint : le professeur ne peut éditer que ses propres groupes. */
    public function edit(Groupe $groupe): View
    {
        $this->verifierAcces($groupe);

        return view('groupes.edit', [
            'groupe'      => $groupe,
            'professeurs' => $this->professeursActifs(),
        ]);
    }

    /** Met à jour une halaqa. Le professeur ne peut pas changer le responsable d'un groupe qui en a déjà un. */
    public function update(Request $request, Groupe $groupe): RedirectResponse
    {
        $this->verifierAcces($groupe);

        $donnees = $this->valider($request);

        // Un professeur garde le responsable existant ; il ne s'attribue le groupe que s'il est orphelin.
        if (auth()->user()->estProfesseur()) {
            $donnees['professeur_id'] = $groupe->professeur_id ?: auth()->id();
        }

        $donnees['nom'] = $donnees['nom'] ?? $groupe->nom ?? $donnees['nom_ar'];

        $groupe->update($donnees);

        return redirect()->route('groupes.index')->with('success', __('Halaqa updated.'));
    }

    /** Supprime une halaqa. 409 (conflit) si elle contient encore des étudiants. Accès restreint pour les professeurs. */
    public function destroy(Groupe $groupe): RedirectResponse
    {
        $this->verifierAcces($groupe);
        abort_if($groupe->etudiants()->exists(), 409, 'تعذر حذف مجموعة تضم طلبة.');

        $groupe->delete();

        return redirect()->route('groupes.index')->with('success', __('Halaqa deleted.'));
    }

    /** Le directeur, le professeur (ses propres مجموعات), le superviseur et le garde général gèrent les مجموعات. */
    private function verifierGestion(): void
    {
        abort_unless(
            auth()->user()->aRole(RoleUtilisateur::DIRECTEUR, RoleUtilisateur::PROFESSEUR, RoleUtilisateur::SUPERVISEUR, RoleUtilisateur::GARDE),
            403
        );
    }

    /** Double contrôle : rôle autorisé (verifierGestion) puis périmètre — 403 si un professeur touche un groupe qui n'est pas le sien. */
    private function verifierAcces(Groupe $groupe): void
    {
        $this->verifierGestion();

        if (auth()->user()->estProfesseur()) {
            abort_unless($groupe->professeur_id === auth()->id(), 403);
        }
    }

    private function professeursActifs()
    {
        /* Le superviseur est aussi un أستاذ : il peut encadrer une مجموعة. */
        return User::encadrants()->where('actif', true)->orderBy('nom_ar')->get();
    }

    /** Règles de validation du formulaire halaqa (niveaux : mubtadi, moutawassit, moutaqaddim, khatma). */
    private function valider(Request $request): array
    {
        return $request->validate([
            'nom_ar'         => ['required', 'string', 'max:255'],
            'nom'            => ['nullable', 'string', 'max:255'],
            'professeur_id'  => ['nullable', 'exists:users,id'],
            'niveau'         => ['nullable', 'in:mubtadi,moutawassit,moutaqaddim,khatma'],
            'salle'          => ['nullable', 'string', 'max:255'],
            'horaire_debut'  => ['nullable', 'date_format:H:i'],
            'horaire_fin'    => ['nullable', 'date_format:H:i'],
            'capacite'       => ['required', 'integer', 'min:1'],
            'annee_scolaire' => ['nullable', 'string', 'max:9'],
            'actif'          => ['sometimes', 'boolean'],
        ]);
    }
}
