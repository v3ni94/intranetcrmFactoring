<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Erzwingt die Einrichtung der Zwei-Faktor-Authentifizierung fuer alle Rollen,
 * fuer die sie Pflicht ist (Abschnitt 18), unabhaengig vom Login-Zeitpunkt der Session.
 */
class EnsureMfaIsConfirmed
{
    private const EXEMPT_ROUTES = ['two-factor.setup', 'two-factor.confirm', 'logout'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->requiresMfa() && ! $user->hasConfirmedTwoFactor() && ! $request->routeIs(...self::EXEMPT_ROUTES)) {
            return redirect()->route('two-factor.setup')
                ->with('status', 'Zwei-Faktor-Authentifizierung ist für Ihre Rolle verpflichtend. Bitte jetzt einrichten.');
        }

        return $next($request);
    }
}
