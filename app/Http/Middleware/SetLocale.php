<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware MULTI-LINGUE historique (sélection ar/fr via session) — probablement
 * devenu INUTILISÉ depuis le passage de l'interface à 100 % arabe, remplacé par
 * ForcerLangueArabe. Conservé pour référence ; à supprimer si aucune route ne
 * l'utilise plus.
 */
class SetLocale
{
    private const LOCALES = ['ar', 'fr'];

    /** Applique la locale stockée en session (« ar » ou « fr »), avec repli sur config('app.locale') si la valeur est inconnue. */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = session('locale', config('app.locale'));

        if (! in_array($locale, self::LOCALES, true)) {
            $locale = config('app.locale');
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
