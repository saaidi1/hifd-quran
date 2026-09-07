<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Changement de mot de passe par l'utilisateur connecté (exige la saisie du
 * mot de passe actuel). Ne pas confondre avec ResetPasswordController
 * (mot de passe oublié, via e-mail).
 */
class PasswordController extends Controller
{
    /** Affiche le formulaire de changement de mot de passe. */
    public function edit(Request $request): View
    {
        return view('auth.password-edit', ['user' => $request->user()]);
    }

    /** Valide le mot de passe actuel (règle current_password) puis enregistre le nouveau (confirmé, 8 caractères min). */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password:web'],
            'password'         => ['required', 'confirmed', 'min:8'],
        ], [
            'current_password.required' => __('Please enter your current password.'),
            'current_password.current_password' => __('The current password is incorrect.'),
            'password.required'  => __('Please enter a new password.'),
            'password.confirmed' => __('The password confirmation does not match.'),
            'password.min'       => __('The password must be at least 8 characters.'),
        ]);

        // Le cast "hashed" du modèle User hache automatiquement le mot de passe.
        $request->user()->update(['password' => $request->password]);

        return back()->with('success', __('Password changed successfully.'));
    }
}
