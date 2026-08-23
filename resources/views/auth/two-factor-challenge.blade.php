<x-guest-layout>
    <h2 class="text-lg font-semibold text-aurevia-navy mb-1">Zwei-Faktor-Bestätigung</h2>
    <p class="text-sm text-aurevia-label-gray mb-4">
        Bitte geben Sie den 6-stelligen Code aus Ihrer Authenticator-App ein, oder alternativ
        einen Ihrer Wiederherstellungscodes.
    </p>

    @if($demoCode)
        <div class="bg-amber-50 border border-amber-200 text-amber-800 rounded-md p-3 mb-4 text-sm">
            Demo-Komfort: aktuell gültiger Code für diesen Demo-Zugang: <span class="font-mono font-semibold">{{ $demoCode }}</span>
            (30 Sekunden gültig, echte TOTP-Prüfung — kein Bypass).
        </div>
    @endif

    <form method="POST" action="{{ route('two-factor.challenge') }}" class="space-y-3">
        @csrf
        <x-input-label for="code" value="Code" />
        <x-text-input id="code" name="code" class="w-full" :value="$demoCode" autofocus required />
        <x-input-error :messages="$errors->get('code')" />
        <x-primary-button>Bestätigen</x-primary-button>
    </form>

    <a href="{{ route('login') }}" class="block mt-6 pt-4 border-t border-aurevia-mist text-sm text-aurevia-label-gray hover:text-aurevia-navy underline">
        Zurück zum Login
    </a>
</x-guest-layout>
