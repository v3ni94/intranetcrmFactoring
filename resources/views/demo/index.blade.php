<x-app-layout>
    <x-slot name="header">{{ __('Demo-Steuerung') }}</x-slot>

    <div class="bg-white rounded-lg border border-aurevia-mist p-6 mb-6">
        <div class="grid grid-cols-2 gap-2 text-sm mb-4">
            <div class="text-aurevia-label-gray">{{ __('Mandant') }}</div><div class="text-right">{{ $tenant->name ?? '–' }}</div>
            <div class="text-aurevia-label-gray">{{ __('Demo-Seed') }}</div><div class="text-right">{{ $tenant->demo_seed_id ?? '–' }}</div>
            <div class="text-aurevia-label-gray">{{ __('Demo-Datensätze aktuell') }}</div><div class="text-right font-semibold">{{ $recordCount }}</div>
        </div>

        <form method="POST" action="{{ route('demo.reset') }}" onsubmit="return confirm('{{ __('Demo wirklich auf die definierte Ausgangslage zurücksetzen? Alle aktuellen Demo-Daten werden ersetzt.') }}');">
            @csrf
            <button class="text-sm text-white bg-aurevia-navy px-3 py-1.5 rounded-md hover:bg-aurevia-navy/90">{{ __('Demo zurücksetzen') }}</button>
        </form>
    </div>

    {{-- v3.03: Vorfuehr-Testdaten auf dem aktuellen Mandanten (auch Produktivsystem) --}}
    <div class="bg-white rounded-lg border border-aurevia-mist p-6 mb-6">
        <h2 class="text-sm font-semibold text-aurevia-navy mb-2">{{ __('Testdaten für Vorführzwecke') }}</h2>
        <p class="text-sm text-aurevia-label-gray mb-3">
            {{ __('Spielt einen umfangreichen, vollständig fiktiven Datensatz ein: 100 Medizin-Kunden (Ärzte, Zahnärzte, Apotheken, Dentallabore, Tierärzte, MVZ, Kliniken) mit Verträgen und Ratings, drei Investoren mit monatlichen Ausschüttungen seit 2025, Forderungen über 2025/2026, Kosten, Abwicklungskonten und unterschriebene Musterverträge. Alle Datensätze sind als Testdaten markiert und hier rückstandslos löschbar.') }}
        </p>
        <div class="grid grid-cols-2 gap-2 text-sm mb-4 max-w-sm">
            <div class="text-aurevia-label-gray">{{ __('Testdatensätze aktuell') }}</div><div class="text-right font-semibold">{{ $showcaseCount }}</div>
            <div class="text-aurevia-label-gray">{{ __('Datensätze gesamt (inkl. eigener)') }}</div><div class="text-right">{{ $allCount }}</div>
        </div>

        <div class="flex flex-wrap gap-3">
            @unless($hasShowcase)
                <form method="POST" action="{{ route('demo.showcase-seed') }}" onsubmit="return confirm('{{ __('Testdaten jetzt einspielen? Der Vorgang kann einige Minuten dauern.') }}');">
                    @csrf
                    <button class="text-sm text-white bg-aurevia-navy px-3 py-1.5 rounded-md hover:bg-aurevia-navy/90">{{ __('Testdaten einspielen') }}</button>
                </form>
            @endunless
        </div>

        @if($hasShowcase)
            <form method="POST" action="{{ route('demo.showcase-purge') }}" class="mt-2 space-y-3 max-w-sm"
                  onsubmit="return confirm('{{ __('Möchten Sie die Testdaten endgültig und unwiderruflich löschen? Eigene, nicht als Testdaten markierte Daten bleiben erhalten.') }}');">
                @csrf
                <x-input-label value="{{ __('Ihr Passwort (erneute Bestätigung)') }}" />
                <x-text-input type="password" name="password" class="w-full" required />
                <button class="text-sm text-white bg-red-700 px-3 py-1.5 rounded-md hover:bg-red-800">{{ __('Testdaten endgültig löschen') }}</button>
                <p class="text-xs text-aurevia-label-gray">{{ __('Danach können die Testdaten jederzeit neu eingespielt werden.') }}</p>
            </form>
        @endif
    </div>

    <div class="bg-white rounded-lg border border-red-300 p-6 mb-6">
        <h2 class="text-sm font-semibold text-red-700 mb-2">{{ __('Alles löschen (auch selbst angelegte Daten)') }}</h2>
        <p class="text-sm text-aurevia-label-gray mb-3">
            {{ __('Löscht sämtliche Bewegungs- und Stammdaten dieses Mandanten, einschließlich selbst angelegter Kunden, Verträge und Vorgänge. Nutzer, Rollen und der Mandant bleiben erhalten. Der Vorgang ist endgültig und unwiderruflich und erfordert die erneute Passworteingabe.') }}
        </p>
        <form method="POST" action="{{ route('demo.purge-all') }}" class="space-y-3 max-w-sm"
              onsubmit="return confirm('{{ __('Wollen Sie wirklich ALLE Daten endgültig und unwiderruflich löschen? Dies umfasst auch selbst angelegte Daten und kann nicht rückgängig gemacht werden.') }}');">
            @csrf
            <x-input-label value="{{ __('Zur Bestätigung \"ALLES LÖSCHEN\" eingeben') }}" />
            <x-text-input name="confirmation" class="w-full" required />
            <x-input-label value="{{ __('Ihr Passwort (erneute Bestätigung)') }}" />
            <x-text-input type="password" name="password" class="w-full" required />
            <button class="text-sm text-white bg-red-700 px-3 py-1.5 rounded-md hover:bg-red-800">{{ __('Alles endgültig löschen') }}</button>
        </form>
    </div>

    <div class="bg-white rounded-lg border border-red-200 p-6">
        <h2 class="text-sm font-semibold text-red-700 mb-2">{{ __('Alle Demo-Daten löschen') }}</h2>
        <p class="text-sm text-aurevia-label-gray mb-3">
            {{ __('Unwiderruflich. Löscht ausschließlich Datensätze mit is_demo = true im Demo-Mandanten. Nutzer, Rollen und der Mandant selbst bleiben erhalten. Erfordert erneute Passworteingabe.') }}
        </p>
        <form method="POST" action="{{ route('demo.delete') }}" class="space-y-3 max-w-sm" onsubmit="return confirm('{{ __('Wirklich ALLE Demo-Daten unwiderruflich löschen?') }}');">
            @csrf
            <x-input-label value="{{ __('Zur Bestätigung \"DEMO LÖSCHEN\" eingeben') }}" />
            <x-text-input name="confirmation" class="w-full" required />
            <x-input-label value="{{ __('Ihr Passwort') }}" />
            <x-text-input type="password" name="password" class="w-full" required />
            <button class="text-sm text-white bg-red-700 px-3 py-1.5 rounded-md hover:bg-red-800">{{ __('Alle Demo-Daten löschen') }}</button>
        </form>
    </div>

    <div class="bg-white rounded-lg border border-aurevia-mist p-4 mt-6">
        <h2 class="text-sm font-semibold text-aurevia-navy mb-3">{{ __('Protokoll (DemoResetLog)') }}</h2>
        @forelse($logs as $l)
            <div class="text-sm flex justify-between border-b border-aurevia-mist/60 py-1.5">
                <span>{{ __(':action durch :performer', ['action' => ucfirst($l->action), 'performer' => $l->performer->name ?? '–']) }}</span>
                <span>{{ __(':count Datensätze · :time', ['count' => $l->affected_records, 'time' => $l->performed_at?->format('d.m.Y H:i')]) }}</span>
            </div>
        @empty
            <p class="text-sm text-aurevia-label-gray">{{ __('Noch keine Reset-/Löschvorgänge protokolliert.') }}</p>
        @endforelse
    </div>
</x-app-layout>
