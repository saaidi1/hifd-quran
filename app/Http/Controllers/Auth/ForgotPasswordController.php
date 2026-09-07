<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

/**
 * Demande de réinitialisation de mot de passe : envoie le lien par e-mail
 * via le broker « Password » de Laravel (token à usage unique, expiration).
 */
class ForgotPasswordController extends Controller
{
    /** Affiche le formulaire de demande de lien de réinitialisation. */
    public function showRequestForm(): View
    {
        return view('auth.forgot-password');
    }

    /** Envoie le lien de réinitialisation ; le message affiché ne révèle jamais si l'e-mail existe ou non côté broker (évite l'énumération de comptes). */
    public function sendResetLinkEmail(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => __('Please enter your email address.'),
            'email.email'    => __('Please enter a valid email address.'),
        ]);

        $statut = Password::sendResetLink($request->only('email'));

        return $statut === Password::RESET_LINK_SENT
            ? back()->with('success', __('We have sent you a password reset link by email.'))
            : back()->withErrors(['email' => __('No account is associated with this email address.')])->onlyInput('email');
    }
}
