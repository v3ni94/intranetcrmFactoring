<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email:strict'],
        ]);

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        //
        // Neutrale Antwort in allen Faellen: verhindert sowohl den 500er bei
        // fehlerhafter SMTP-Konfiguration als auch User-Enumeration ueber
        // unterschiedliche Fehlermeldungen.
        try {
            $status = Password::sendResetLink(
                $request->only('email')
            );
        } catch (TransportExceptionInterface $e) {
            report($e);

            return back()->with('status', __('passwords.sent'));
        }

        // Auch bei unbekannter Adresse dieselbe neutrale Meldung (keine User-Enumeration).
        // Throttling-Status wird weiterhin angezeigt, verraet aber keine Existenz.
        return $status == Password::RESET_THROTTLED
                    ? back()->withInput($request->only('email'))->withErrors(['email' => __($status)])
                    : back()->with('status', __(Password::RESET_LINK_SENT));
    }
}
