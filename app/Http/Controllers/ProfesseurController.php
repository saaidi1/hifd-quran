<?php

namespace App\Http\Controllers;

use App\Enums\RoleUtilisateur;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Gestion des comptes utilisateurs (« professeurs » au sens large : tous les
 * rôles — directeur, superviseur, garde, professeur) : création, édition,
 * activation/désactivation.
 *
 * Réservé à la direction (routes protégées par le middleware role).
 * Le mot de passe est haché via Hash::make ; l'activation se fait par toggle.
 */
class ProfesseurController extends Controller
{
    /** Liste les comptes (hors utilisateur connecté) avec leur nombre de groupes, triés par nom arabe. */
    public function index(): View
    {
        $professeurs = User::query()
            ->withCount('groupes')
            ->whereNot('id', auth()->id())
            ->orderBy('nom_ar')
            ->get();

        return view('professeurs.index', ['professeurs' => $professeurs]);
    }

    /** Formulaire de création d'un compte utilisateur. */
    public function create(): View
    {
        return view('professeurs.create');
    }

    /** Crée un compte : hachage du mot de passe, champ « name » reconstruit à partir de prénom + nom. */
    public function store(Request $request): RedirectResponse
    {
        $donnees = $this->valider($request);

        // « name » (champ technique Laravel) est recalculé depuis les champs métier.
        User::create([
            'name'        => trim(($donnees['prenom'] ?? '') . ' ' . ($donnees['nom'] ?? '')),
            'nom'         => $donnees['nom'] ?? null,
            'prenom'      => $donnees['prenom'] ?? null,
            'nom_ar'      => $donnees['nom_ar'],
            'email'       => $donnees['email'],
            'password'    => Hash::make($donnees['password']),
            'role'        => $donnees['role'],
            'telephone'   => $donnees['telephone'] ?? null,
            'specialite'  => $donnees['specialite'] ?? null,
            'actif'       => $request->boolean('actif'),
        ]);

        return redirect()->route('professeurs.index')->with('success', __('Account created successfully.'));
    }

    /** Formulaire d'édition d'un compte existant. */
    public function edit(User $professeur): View
    {
        return view('professeurs.create', ['professeur' => $professeur]);
    }

    /** Met à jour un compte. Le mot de passe n'est modifié que s'il est resaisi (champ optionnel en édition). */
    public function update(Request $request, User $professeur): RedirectResponse
    {
        $donnees = $this->valider($request, $professeur);

        $professeur->update([
            'name'       => trim(($donnees['prenom'] ?? '') . ' ' . ($donnees['nom'] ?? '')),
            'nom'        => $donnees['nom'] ?? null,
            'prenom'     => $donnees['prenom'] ?? null,
            'nom_ar'     => $donnees['nom_ar'],
            'email'      => $donnees['email'],
            'role'       => $donnees['role'],
            'telephone'  => $donnees['telephone'] ?? null,
            'specialite' => $donnees['specialite'] ?? null,
            'actif'      => $request->boolean('actif'),
        ]);

        if (! empty($donnees['password'])) {
            $professeur->update(['password' => Hash::make($donnees['password'])]);
        }

        return redirect()->route('professeurs.index')->with('success', __('Account updated.'));
    }

    /** Bascule actif/inactif. Un utilisateur ne peut pas désactiver son propre compte (garde-fou anti-blocage). */
    public function toggle(User $professeur): RedirectResponse
    {
        if ($professeur->id === auth()->id()) {
            return back()->with('error', __('You cannot deactivate your own account.'));
        }

        $professeur->update(['actif' => ! $professeur->actif]);

        return back()->with('success', $professeur->actif ? __('Account activated.') : __('Account deactivated.'));
    }

    /** Validation partagée create/update : e-mail unique (ignoré pour $ignore en édition), rôle issu de l'enum, mot de passe requis à la création seulement. */
    private function valider(Request $request, ?User $ignore = null): array
    {
        return $request->validate([
            'nom_ar'     => ['required', 'string', 'max:255'],
            'prenom'     => ['required', 'string', 'max:255'],
            'nom'        => ['required', 'string', 'max:255'],
            'email'      => ['required', 'email', Rule::unique('users', 'email')->ignore($ignore)],
            'role'       => ['required', Rule::enum(RoleUtilisateur::class)],
            'telephone'  => ['nullable', 'string', 'max:30'],
            'specialite' => ['nullable', 'string', 'max:255'],
            'password'   => [$ignore ? 'nullable' : 'required', 'string', 'min:8'],
            'actif'      => ['sometimes', 'boolean'],
        ]);
    }
}
