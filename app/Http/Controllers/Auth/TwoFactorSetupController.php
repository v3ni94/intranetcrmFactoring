<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;
use PragmaRX\Google2FA\Google2FA;

/**
 * Einrichtung der Zwei-Faktor-Authentifizierung (TOTP). MFA ist fuer alle internen,
 * Investor- und Beirats-Nutzer verpflichtend (Abschnitt 18: "MFA zwingend").
 */
class TwoFactorSetupController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();
        $google2fa = new Google2FA;

        if (! $user->two_factor_secret) {
            $user->forceFill(['two_factor_secret' => $google2fa->generateSecretKey()])->save();
        }

        $otpauthUrl = $google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $user->two_factor_secret
        );

        return view('auth.two-factor-setup', [
            'secret' => $user->two_factor_secret,
            'otpauthUrl' => $otpauthUrl,
            'confirmed' => $user->hasConfirmedTwoFactor(),
        ]);
    }

    public function confirm(Request $request): RedirectResponse
    {
        $data = $request->validate(['code' => 'required|string']);
        $user = $request->user();
        $google2fa = new Google2FA;

        if (! $user->two_factor_secret || ! $google2fa->verifyKey($user->two_factor_secret, $data['code'])) {
            return back()->withErrors(['code' => 'Der eingegebene Code ist ungültig oder abgelaufen.']);
        }

        $recoveryCodes = collect(range(1, 8))->map(fn () => Str::upper(Str::random(10)))->all();

        // Nur gehasht speichern (wie Passwoerter): ein DB-Leak gibt keine nutzbaren
        // Codes preis. Die Klartext-Codes werden genau einmal angezeigt (Session-Flash).
        $user->forceFill([
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => array_map(fn (string $code) => Hash::make($code), $recoveryCodes),
            'mfa_enabled' => true,
        ])->save();

        return redirect()->route('two-factor.setup')->with('recovery_codes', $recoveryCodes)->with('status', 'Zwei-Faktor-Authentifizierung aktiviert.');
    }
}
