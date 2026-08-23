<x-app-layout>
    <x-slot name="header">Demo-Steuerung</x-slot>

    <div class="bg-white rounded-lg border border-aurevia-mist p-6 mb-6">
        <div class="grid grid-cols-2 gap-2 text-sm mb-4">
            <div class="text-aurevia-label-gray">Mandant</div><div class="text-right">{{ $tenant->name ?? '–' }}</div>
            <div class="text-aurevia-label-gray">Demo-Seed</div><div class="text-right">{{ $tenant->demo_seed_id ?? '–' }}</div>
            <div class="text-aurevia-label-gray">Demo-Datensätze aktuell</div><div class="text-right font-semibold">{{ $recordCount }}</div>
        </div>

        <form method="POST" action="{{ route('demo.reset') }}" onsubmit="return confirm('Demo wirklich auf die definierte Ausgangslage zurücksetzen? Alle aktuellen Demo-Daten werden ersetzt.');">
            @csrf
            <button class="text-sm text-white bg-aurevia-navy px-3 py-1.5 rounded-md hover:bg-aurevia-navy/90">Demo zurücksetzen</button>
        </form>
    </div>

    <div class="bg-white rounded-lg border border-red-200 p-6">
        <h2 class="text-sm font-semibold text-red-700 mb-2">Alle Demo-Daten löschen</h2>
        <p class="text-sm text-aurevia-label-gray mb-3">
            Unwiderruflich. Löscht ausschließlich Datensätze mit is_demo = true im Demo-Mandanten. Nutzer, Rollen und der
            Mandant selbst bleiben erhalten. Erfordert erneute Passworteingabe.
        </p>
        <form method="POST" action="{{ route('demo.delete') }}" class="space-y-3 max-w-sm" onsubmit="return confirm('Wirklich ALLE Demo-Daten unwiderruflich löschen?');">
            @csrf
            <x-input-label value='Zur Bestätigung "DEMO LÖSCHEN" eingeben' />
            <x-text-input name="confirmation" class="w-full" required />
            <x-input-label value="Ihr Passwort" />
            <x-text-input type="password" name="password" class="w-full" required />
            <button class="text-sm text-white bg-red-700 px-3 py-1.5 rounded-md hover:bg-red-800">Alle Demo-Daten löschen</button>
        </form>
    </div>

    <div class="bg-white rounded-lg border border-aurevia-mist p-4 mt-6">
        <h2 class="text-sm font-semibold text-aurevia-navy mb-3">Protokoll (DemoResetLog)</h2>
        @forelse($logs as $l)
            <div class="text-sm flex justify-between border-b border-aurevia-mist/60 py-1.5">
                <span>{{ ucfirst($l->action) }} durch {{ $l->performer->name ?? '–' }}</span>
                <span>{{ $l->affected_records }} Datensätze · {{ $l->performed_at?->format('d.m.Y H:i') }}</span>
            </div>
        @empty
            <p class="text-sm text-aurevia-label-gray">Noch keine Reset-/Löschvorgänge protokolliert.</p>
        @endforelse
    </div>
</x-app-layout>
