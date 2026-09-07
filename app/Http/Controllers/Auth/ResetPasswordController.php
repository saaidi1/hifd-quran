<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

/**
 * Réinitialisation du mot de passe après clic sur le lien reçu par e-mail
 * (complète ForgotPasswordController) : valide le token, réinitialise le mot
 * de passe puis redirige vers la page de connexion.
 */
class ResetPasswordController extends Controller
{
    /** Affiche le formulaire de réinitialisation avec le token et l'e-mail pré-rempli depuis l'URL. */
    public function showResetForm(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    /** Vérifie token + e-mail via le broker Password, applique le nouveau mot de passe (haché par le cast du modèle), et renvoie une erreur si le lien est invalide/expiré. */
    public function reset(Request $request): RedirectResponse
    {
        $request->validate([
            'token'    => ['required'],
            'email'    => ['required', 'email'],
            'password' => ['required', 'confirmed', 'min:8'],
        ], [
            'email.required'          => __('Please enter your email address.'),
            'email.email'             => __('Please enter a valid email address.'),
            'password.required'       => __('Please enter a new password.'),
            'password.confirmed'      => __('The password confirmation does not match.'),
            'password.min'            => __('The password must be at least 8 characters.'),
        ]);

        // Le cast "hashed" du modèle User hache automatiquement le mot de passe.
        $statut = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => $password])->save();
            }
        );

        return $statut === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('success', __('Password reset successfully. You can now sign in.'))
            : back()->withErrors(['email' => __('This password reset link is invalid or has expired.')])->onlyInput('email');
    }
}
