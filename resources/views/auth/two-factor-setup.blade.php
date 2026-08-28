<x-guest-layout>
    <h2 class="text-lg font-semibold text-aurevia-navy mb-1">{{ __('Zwei-Faktor-Authentifizierung') }}</h2>
    <p class="text-sm text-aurevia-label-gray mb-4">
        {{ __('Für Ihre Rolle verpflichtend. Richten Sie eine Authenticator-App (z. B. Google Authenticator, Microsoft Authenticator, Authy) per QR-Code oder mit dem manuellen Schlüssel ein.') }}
    </p>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    @if(session('recovery_codes'))
        <div class="bg-amber-50 border border-amber-200 rounded-md p-3 mb-4 text-sm">
            <strong>{{ __('Wiederherstellungscodes (nur jetzt sichtbar, sicher aufbewahren):') }}</strong>
            <div class="grid grid-cols-2 gap-1 mt-2 font-mono text-xs">
                @foreach(session('recovery_codes') as $code)
                    <div>{{ $code }}</div>
                @endforeach
            </div>
        </div>
    @endif

    @if($confirmed)
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-md p-3 text-sm">
            {{ __('Zwei-Faktor-Authentifizierung ist für Ihr Konto aktiv.') }}
        </div>
    @else
        <div class="bg-white border border-aurevia-mist rounded-md p-4 mb-4 flex flex-col items-center">
            <p class="text-[11px] uppercase tracking-wide text-aurevia-label-gray mb-2">{{ __('QR-Code mit der Authenticator-App scannen') }}</p>
            <div class="bg-white p-2 rounded">{!! $qrSvg !!}</div>
        </div>

        <div class="bg-aurevia-pearl rounded-md p-3 mb-4">
            <p class="text-[11px] uppercase tracking-wide text-aurevia-label-gray mb-1">{{ __('Alternativ: manueller Schlüssel') }}</p>
            <p class="font-mono text-sm break-all">{{ $secret }}</p>
            <p class="text-[11px] text-aurevia-label-gray mt-2">otpauth-URI (für Apps mit Import-Funktion): <span class="break-all">{{ $otpauthUrl }}</span></p>
        </div>

        <form method="POST" action="{{ route('two-factor.confirm') }}" class="space-y-3">
            @csrf
            <x-input-label for="code" value="{{ __('6-stelliger Code aus der App') }}" />
            <x-text-input id="code" name="code" class="w-full" inputmode="numeric" autofocus required />
            <x-input-error :messages="$errors->get('code')" />
            <x-primary-button>{{ __('Aktivieren') }}</x-primary-button>
        </form>
    @endif

    <form method="POST" action="{{ route('logout') }}" class="mt-6 pt-4 border-t border-aurevia-mist">
        @csrf
        <button type="submit" class="text-sm text-aurevia-label-gray hover:text-aurevia-navy underline">{{ __('Abmelden') }}</button>
    </form>
</x-guest-layout>
