<div style="font-family: ui-sans-serif, system-ui, sans-serif; color: #1C1C1C; max-width: 560px;">
    <div style="background: #0E2A47; color: #fff; padding: 14px 18px; border-radius: 8px 8px 0 0;">
        <strong>AUREVIA FACTORING</strong> · {{ __('Intranet') }}
    </div>
    <div style="border: 1px solid #D9DDE3; border-top: none; padding: 18px; font-size: 14px;">
        <p>{{ __('Guten Tag :name,', ['name' => $user->name]) }}</p>
        @if($isReset)
            <p>{{ __('für Ihr Konto wurde ein Passwort-Reset ausgelöst. Über den folgenden Link vergeben Sie ein neues Passwort:') }}</p>
        @else
            <p>{!! __('für Sie wurde ein Zugang zum Aurevia Intranet eingerichtet (Benutzername: :email). Über den folgenden Link vergeben Sie Ihr persönliches Passwort:', ['email' => '<strong>'.$user->email.'</strong>']) !!}</p>
        @endif
        <p style="margin: 18px 0;">
            <a href="{{ $setPasswordUrl }}"
               style="background: #0E2A47; color: #fff; padding: 10px 18px; border-radius: 6px; text-decoration: none;">
                {{ __('Passwort festlegen') }}
            </a>
        </p>
        <p style="font-size: 12px; color: #8A94A0;">
            {{ __('Der Link ist zeitlich begrenzt gültig. Falls er abgelaufen ist, nutzen Sie auf der Login-Seite die Funktion „Passwort vergessen?" mit Ihrer E-Mail-Adresse. Je nach Rolle werden Sie nach dem ersten Login durch die Einrichtung der Zwei-Faktor-Authentifizierung geführt (Authenticator-App, QR-Code-Scan).') }}
        </p>
        <p>{{ __('Login') }}: <a href="{{ route('login') }}">{{ route('login') }}</a></p>
    </div>
    <p style="font-size: 11px; color: #8A94A0; padding: 10px 2px;">
        {{ __('Aurevia Intranet · Ein Produkt der Müller Holding AG · Diese E-Mail wurde automatisch erzeugt. Wenn Sie diese Nachricht nicht erwartet haben, wenden Sie sich an die Systemadministration.') }}
    </p>
</div>
