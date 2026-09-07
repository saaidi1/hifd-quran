<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Modification du profil de l'utilisateur connecté (nom, e-mail, téléphone,
 * spécialité). Accessible à tout utilisateur authentifié ; ne touche ni au
 * rôle ni au mot de passe (voir PasswordController).
 */
class ProfileController extends Controller
{
    /** Affiche le formulaire d'édition du profil de l'utilisateur connecté. */
    public function edit(Request $request): View
    {
        return view('auth.profile-edit', ['user' => $request->user()]);
    }

    /** Met à jour le profil ; l'e-mail doit rester unique (son propre e-mail ignoré dans la règle). */
    public function update(Request $request): RedirectResponse
    {
        $donnees = $request->validate([
            'nom'        => ['required', 'string', 'max:255'],
            'prenom'     => ['required', 'string', 'max:255'],
            'nom_ar'     => ['nullable', 'string', 'max:255'],
            'email'      => ['required', 'email', 'max:255', Rule::unique('users')->ignore($request->user()->id)],
            'telephone'  => ['nullable', 'string', 'max:30'],
            'specialite' => ['nullable', 'string', 'max:255'],
        ], [
            'nom.required'     => __('Please enter your last name.'),
            'prenom.required'  => __('Please enter your first name.'),
            'email.required'   => __('Please enter your email address.'),
            'email.email'      => __('Please enter a valid email address.'),
            'email.unique'     => __('This email address is already used by another account.'),
        ]);

        $request->user()->update($donnees);

        return back()->with('success', __('Profile updated successfully.'));
    }
}
