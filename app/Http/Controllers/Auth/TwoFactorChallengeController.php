<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;
use PragmaRX\Google2FA\Google2FA;

/**
 * Zweiter Faktor beim Login: nach erfolgreicher Passwortpruefung wird der Nutzer
 * erst nach gueltigem TOTP-Code vollstaendig angemeldet (siehe AuthenticatedSessionController).
 * Fehlversuche sind limitiert (Brute-Force-Schutz), akzeptierte TOTP-Codes sind
 * nicht wiederverwendbar (Replay-Schutz ueber verifyKeyNewer).
 */
class TwoFactorChallengeController extends Controller
{
    private const MAX_ATTEMPTS = 5;

    private const LOCKOUT_SECONDS = 300;

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

        abort_unless($user, 419, __('Sitzung abgelaufen, bitte erneut anmelden.'));

        // Zwischen Erstfaktor und Challenge deaktivierte oder ausserhalb des
        // Beschaeftigungszeitraums befindliche Konten abweisen.
        if (! $user->is_active || ! $user->isWithinEmploymentPeriod()) {
            $request->session()->forget(['mfa_challenge_user_id', 'mfa_challenge_remember']);

            return redirect()->route('login')->withErrors(['email' => __('Dieses Konto ist deaktiviert.')]);
        }

        $throttleKey = 'mfa:'.$user->id.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            $request->session()->forget(['mfa_challenge_user_id', 'mfa_challenge_remember']);
            AuditLogger::log('mfa_lockout', User::class, $user->id, [], [], 'Zu viele Fehlversuche bei der Zwei-Faktor-Pruefung');

            return redirect()->route('login')
                ->withErrors(['email' => __('Zu viele Fehlversuche bei der Zwei-Faktor-Prüfung. Bitte melden Sie sich erneut an.')]);
        }

        $google2fa = new Google2FA;

        // Replay-Schutz: verifyKeyNewer akzeptiert nur Codes, die neuer sind als der
        // zuletzt verwendete Zeitschritt, und liefert bei Erfolg den neuen Zeitschritt.
        $validCode = false;
        if ($user->two_factor_secret) {
            // Basiswert 0 statt null: mit null wuerde verifyKeyNewer nur bool liefern
            // und der Zeitschritt wuerde beim ersten Login nicht gespeichert.
            $timestamp = $google2fa->verifyKeyNewer($user->two_factor_secret, $data['code'], (int) ($user->two_factor_last_otp_at ?? 0));
            if ($timestamp !== false) {
                $validCode = true;
                if (is_int($timestamp)) {
                    $user->forceFill(['two_factor_last_otp_at' => $timestamp])->save();
                }
            }
        }

        // Wiederherstellungscodes werden gehasht gespeichert und einmalig verbraucht.
        $validRecovery = false;
        if (! $validCode) {
            $codes = $user->two_factor_recovery_codes ?? [];
            foreach ($codes as $index => $hashed) {
                if (Hash::check(strtoupper($data['code']), $hashed)) {
                    $validRecovery = true;
                    unset($codes[$index]);
                    $user->forceFill(['two_factor_recovery_codes' => array_values($codes)])->save();
                    break;
                }
            }
        }

        if (! $validCode && ! $validRecovery) {
            RateLimiter::hit($throttleKey, self::LOCKOUT_SECONDS);
            AuditLogger::log('mfa_failed', User::class, $user->id, [], [], 'Ungueltiger Zwei-Faktor-Code');

            return back()->withErrors(['code' => __('Der eingegebene Code ist ungültig.')]);
        }

        RateLimiter::clear($throttleKey);
        $request->session()->forget('mfa_challenge_user_id');
        Auth::login($user, $request->session()->pull('mfa_challenge_remember', false));
        $request->session()->regenerate();

        if ($validRecovery) {
            AuditLogger::log('mfa_recovery_used', User::class, $user->id, [], [], 'Login per Wiederherstellungscode');
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
