<x-app-layout>
    <x-slot name="header">Reporting &amp; Exporte</x-slot>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg border border-aurevia-mist p-6">
            <h2 class="text-sm font-semibold text-aurevia-navy mb-2">Forderungen (CSV)</h2>
            <p class="text-sm text-aurevia-label-gray mb-3">Alle Forderungen mit Status, Beträgen und Fälligkeiten.</p>
            <a href="{{ route('reports.receivables') }}" class="text-sm text-white bg-aurevia-navy px-3 py-1.5 rounded-md hover:bg-aurevia-navy/90">CSV exportieren</a>
        </div>
        <div class="bg-white rounded-lg border border-aurevia-mist p-6">
            <h2 class="text-sm font-semibold text-aurevia-navy mb-2">Journal / Nebenbuch (CSV)</h2>
            <p class="text-sm text-aurevia-label-gray mb-3">Alle Buchungszeilen mit Konto, Soll und Haben.</p>
            <a href="{{ route('reports.journal') }}" class="text-sm text-white bg-aurevia-navy px-3 py-1.5 rounded-md hover:bg-aurevia-navy/90">CSV exportieren</a>
        </div>
        <div class="bg-white rounded-lg border border-aurevia-mist p-6">
            <h2 class="text-sm font-semibold text-aurevia-navy mb-2">DATEV-Buchungsstapel (Demo)</h2>
            <p class="text-sm text-aurevia-label-gray mb-3">Sachkonten-Mapping als CSV, Adapter statt Festverdrahtung (Abschnitt 20).</p>
            <a href="{{ route('reports.datev') }}" class="text-sm text-white bg-aurevia-navy px-3 py-1.5 rounded-md hover:bg-aurevia-navy/90">CSV exportieren</a>
        </div>
    </div>

    <p class="text-[11px] text-aurevia-label-gray mt-6">
        Alle Exporte werden im Audit-Trail protokolliert. Sensible Berichte (Board Pack, Investorendaten) sind zusätzlich über
        das Dokumentenmanagement mit Sperrvermerk und Wasserzeichen zu versehen (Roadmap).
    </p>
</x-app-layout>
