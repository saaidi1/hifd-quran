<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware d'autorisation par rôle : refuse la requête (403) si
 * l'utilisateur n'est pas authentifié/actif ou si son rôle ne figure pas
 * dans la liste des rôles autorisés passés en paramètres.
 *
 * Usage dans les routes : ->middleware('role:directeur,superviseur')
 * Enregistrement (bootstrap/app.php) :
 *   $middleware->alias(['role' => \App\Http\Middleware\EnsureRole::class]);
 */
class EnsureRole
{
    /** Vérifie l'authentification, le compte actif puis l'appartenance du rôle aux rôles autorisés (ex. role:directeur,superviseur). 403 sinon. */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        abort_if(! $user || ! $user->actif, 403, 'Compte inactif ou non authentifié.');
        abort_unless(in_array($user->role->value, $roles, true), 403, 'Accès non autorisé pour votre rôle.');

        return $next($request);
    }
}
