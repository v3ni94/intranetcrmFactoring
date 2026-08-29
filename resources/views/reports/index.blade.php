<x-app-layout>
    <x-slot name="header">{{ __('Reporting & Exporte') }}</x-slot>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg border border-aurevia-mist p-6">
            <h2 class="text-sm font-semibold text-aurevia-navy mb-2">{{ __('Forderungen (CSV)') }}</h2>
            <p class="text-sm text-aurevia-label-gray mb-3">{{ __('Alle Forderungen mit Status, Beträgen und Fälligkeiten.') }}</p>
            <a href="{{ route('reports.receivables') }}" class="text-sm text-white bg-aurevia-navy px-3 py-1.5 rounded-md hover:bg-aurevia-navy/90">{{ __('CSV exportieren') }}</a>
        </div>
        <div class="bg-white rounded-lg border border-aurevia-mist p-6">
            <h2 class="text-sm font-semibold text-aurevia-navy mb-2">{{ __('Journal / Nebenbuch (CSV)') }}</h2>
            <p class="text-sm text-aurevia-label-gray mb-3">{{ __('Alle Buchungszeilen mit Konto, Soll und Haben.') }}</p>
            <a href="{{ route('reports.journal') }}" class="text-sm text-white bg-aurevia-navy px-3 py-1.5 rounded-md hover:bg-aurevia-navy/90">{{ __('CSV exportieren') }}</a>
        </div>
        <div class="bg-white rounded-lg border border-aurevia-mist p-6">
            <h2 class="text-sm font-semibold text-aurevia-navy mb-2">{{ __('DATEV-Buchungsstapel (Demo)') }}</h2>
            <p class="text-sm text-aurevia-label-gray mb-3">{{ __('Sachkonten-Mapping als CSV, Adapter statt Festverdrahtung (Abschnitt 20).') }}</p>
            <a href="{{ route('reports.datev') }}" class="text-sm text-white bg-aurevia-navy px-3 py-1.5 rounded-md hover:bg-aurevia-navy/90">{{ __('CSV exportieren') }}</a>
        </div>
        <div class="bg-white rounded-lg border border-aurevia-mist p-6">
            <h2 class="text-sm font-semibold text-aurevia-navy mb-2">{{ __('KPI-Report per E-Mail') }}</h2>
            <p class="text-sm text-aurevia-label-gray mb-3">{{ __('Kompakte Kennzahlenübersicht sofort an eine Adresse senden.') }}</p>
            <form method="POST" action="{{ route('reports.send-kpi') }}" class="flex gap-2">
                @csrf
                <input type="email" name="recipient_email" required placeholder="empfaenger@beispiel.de" class="flex-1 text-sm rounded-md border-aurevia-mist" />
                <button class="text-sm text-white bg-aurevia-navy px-3 py-1.5 rounded-md hover:bg-aurevia-navy/90 whitespace-nowrap">{{ __('Jetzt senden') }}</button>
            </form>
            @error('recipient_email')<p class="text-xs text-red-700 mt-2">{{ $message }}</p>@enderror
        </div>
    </div>

    {{-- Automatische Reports (v3.00) --}}
    <div class="bg-white rounded-lg border border-aurevia-mist p-6 mt-6">
        <h2 class="text-sm font-semibold text-aurevia-navy mb-2">{{ __('Automatische Reports') }}</h2>
        <p class="text-sm text-aurevia-label-gray mb-3">{{ __('KPI-Report regelmäßig per E-Mail (Versand morgens über den Scheduler).') }}</p>
        <form method="POST" action="{{ route('reports.subscriptions.store') }}" class="flex flex-wrap gap-2 mb-4">
            @csrf
            <input type="email" name="recipient_email" required placeholder="empfaenger@beispiel.de" class="text-sm rounded-md border-aurevia-mist" />
            <select name="frequency" class="text-sm rounded-md border-aurevia-mist">
                <option value="taeglich">{{ __('täglich') }}</option>
                <option value="woechentlich">{{ __('wöchentlich') }}</option>
                <option value="monatlich">{{ __('monatlich') }}</option>
            </select>
            <button class="text-sm text-white bg-aurevia-navy px-3 py-1.5 rounded-md hover:bg-aurevia-navy/90">{{ __('Einrichten') }}</button>
        </form>
        <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="text-[11px] uppercase text-aurevia-label-gray">
                <tr><th class="text-left pb-2">{{ __('Empfänger') }}</th><th class="text-left pb-2">{{ __('Frequenz') }}</th><th class="text-left pb-2">{{ __('Zuletzt gesendet') }}</th><th class="text-left pb-2">{{ __('Status') }}</th><th class="text-right pb-2"></th></tr>
            </thead>
            <tbody>
            @forelse($subscriptions as $sub)
                <tr class="border-t border-aurevia-mist/60">
                    <td class="py-1.5">{{ $sub->recipient_email }}</td>
                    <td class="py-1.5">{{ $sub->frequency }}</td>
                    <td class="py-1.5">{{ $sub->last_sent_at?->format('d.m.Y H:i') ?? '–' }}</td>
                    <td class="py-1.5">{{ $sub->active ? __('aktiv') : __('pausiert') }}</td>
                    <td class="py-1.5 text-right">
                        <form method="POST" action="{{ route('reports.subscriptions.toggle', $sub) }}" class="inline">
                            @csrf
                            <button class="text-xs text-aurevia-navy underline hover:no-underline">{{ $sub->active ? __('Pausieren') : __('Aktivieren') }}</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="py-4 text-center text-aurevia-label-gray">{{ __('Keine automatischen Reports eingerichtet.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
        </div>
    </div>

    <p class="text-[11px] text-aurevia-label-gray mt-6">
        {{ __('Alle Exporte werden im Audit-Trail protokolliert. Sensible Berichte (Board Pack, Investorendaten) sind zusätzlich über das Dokumentenmanagement mit Sperrvermerk und Wasserzeichen zu versehen (Roadmap).') }}
    </p>
</x-app-layout>
