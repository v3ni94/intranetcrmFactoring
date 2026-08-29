<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $user = Auth::user();

        // MFA ist fuer interne/Investor-/Beirats-Nutzer Pflicht (Abschnitt 18). Ist der
        // zweite Faktor bereits bestaetigt, wird der Login sofort wieder zurueckgenommen
        // und erst nach gueltigem TOTP-Code final abgeschlossen (siehe TwoFactorChallengeController).
        if ($user->requiresMfa() && $user->hasConfirmedTwoFactor()) {
            $remember = $request->boolean('remember');
            Auth::logout();

            $request->session()->regenerate();
            $request->session()->put('mfa_challenge_user_id', $user->id);
            $request->session()->put('mfa_challenge_remember', $remember);

            return redirect()->route('two-factor.challenge');
        }

        $request->session()->regenerate();

        // MFA-Pflicht, aber noch nicht eingerichtet: Einrichtung erzwingen (siehe auch
        // Middleware EnsureMfaIsConfirmed fuer alle Folgeaufrufe).
        if ($user->requiresMfa() && ! $user->hasConfirmedTwoFactor()) {
            return redirect()->route('two-factor.setup')
                ->with('status', __('Zwei-Faktor-Authentifizierung ist für Ihre Rolle verpflichtend. Bitte jetzt einrichten.'));
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
