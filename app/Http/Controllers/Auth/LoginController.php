<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Authentification manuelle (sans trait Laravel Breeze) : connexion par
 * e-mail/mot de passe avec contrôle du compte actif, et déconnexion.
 *
 * Routes : GET/POST /login, POST /logout. Après login, redirection vers le
 * tableau de bord (ou l'URL « intended »).
 */
class LoginController extends Controller
{
    /** Affiche le formulaire de connexion. */
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    /** Tente la connexion : identifiants invalides → erreur générique ; compte désactivé (actif = false) → déconnexion forcée et message dédié. */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Pré-charge l'utilisateur pour distinguer « identifiants invalides » de « compte désactivé ».
        $user = \App\Models\User::where('email', $credentials['email'])->first();

        if (! $user || ! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'البريد الإلكتروني أو كلمة المرور غير صحيحة.',
            ]);
        }

        if (! $user->actif) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'هذا الحساب معطل. يرجى الاتصال بالإدارة.',
            ]);
        }

        return redirect()->intended(route('dashboard'));
    }

    /** Déconnecte l'utilisateur : session invalidée + jeton CSRF régénéré, puis retour au formulaire de login. */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
