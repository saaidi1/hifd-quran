<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware imposant la locale « ar » sur chaque requête : l'interface étant
 * 100 % arabe (RTL), aucune autre langue n'est proposée. Filament bascule
 * alors automatiquement en RTL.
 */
/** Force la locale arabe : Filament bascule alors automatiquement en RTL. */
class ForcerLangueArabe
{
    /** Fixe la locale de l'application à « ar » puis poursuit la requête sans condition. */
    public function handle(Request $request, Closure $next): Response
    {
        app()->setLocale('ar');

        return $next($request);
    }
}
