<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use PragmaRX\Google2FA\Google2FA;

/**
 * Zweiter Faktor beim Login: nach erfolgreicher Passwortpruefung wird der Nutzer
 * erst nach gueltigem TOTP-Code vollstaendig angemeldet (siehe AuthenticatedSessionController).
 */
class TwoFactorChallengeController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('mfa_challenge_user_id')) {
            return redirect()->route('login');
        }

        // Demo-Komfort: fuer vorkonfigurierte Demo-Nutzer wird der aktuell gueltige
        // TOTP-Code angezeigt, damit der gefuehrte Rollenwechsel (Abschnitt 21.2) ohne
        // eigene Authenticator-App moeglich bleibt. Die Pruefung selbst bleibt echt.
        $demoCode = null;
        if (config('aurevia.demo_mode')) {
            $user = User::find($request->session()->get('mfa_challenge_user_id'));
            if ($user?->is_demo && $user->two_factor_secret) {
                $demoCode = (new Google2FA)->getCurrentOtp($user->two_factor_secret);
            }
        }

        return view('auth.two-factor-challenge', compact('demoCode'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['code' => 'required|string']);
        $userId = $request->session()->get('mfa_challenge_user_id');
        $user = $userId ? User::find($userId) : null;

        abort_unless($user, 419, 'Sitzung abgelaufen, bitte erneut anmelden.');

        $google2fa = new Google2FA;
        $validCode = $user->two_factor_secret && $google2fa->verifyKey($user->two_factor_secret, $data['code']);
        $validRecovery = in_array(strtoupper($data['code']), $user->two_factor_recovery_codes ?? [], true);

        if (! $validCode && ! $validRecovery) {
            return back()->withErrors(['code' => 'Der eingegebene Code ist ungültig.']);
        }

        if ($validRecovery) {
            $remaining = array_values(array_diff($user->two_factor_recovery_codes, [strtoupper($data['code'])]));
            $user->forceFill(['two_factor_recovery_codes' => $remaining])->save();
        }

        $request->session()->forget('mfa_challenge_user_id');
        Auth::login($user, $request->session()->pull('mfa_challenge_remember', false));
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
