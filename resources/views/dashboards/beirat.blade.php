<x-app-layout>
    <x-slot name="header">Beirat / Aufsichtsrat – Read-only</x-slot>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <x-kpi-card label="Ausstehendes Portfolio" :value="eur($outstandingPortfolio)" />
        <x-kpi-card label="Bruttoertrag" :value="eur($grossRevenue)" />
        <x-kpi-card label="Deckungsbeitrag" :value="eur($contributionMargin)" />
        <x-kpi-card label="Überfälligkeitsquote" :value="pct($overdueRatio)" />
        <x-kpi-card label="Top-10-Konzentration" :value="pct($top10)" />
        <x-kpi-card label="Covenant-Warnungen" :value="$covenantWarnings" tone="{{ $covenantWarnings > 0 ? 'warn' : 'good' }}" />
        <x-kpi-card label="Neugeschäft (30 Tage)" :value="$newBusinessCount.' Ankäufe'" />
    </div>

    <div class="bg-white rounded-lg border border-aurevia-mist p-4 mb-6">
        <h2 class="text-sm font-semibold text-aurevia-navy mb-3">Investorenlinien</h2>
        <table class="w-full text-sm">
            <thead class="text-[11px] uppercase text-aurevia-label-gray">
                <tr><th class="text-left pb-2">Fazilität</th><th class="text-left pb-2">Investor</th><th class="text-right pb-2">Zusage</th><th class="text-right pb-2">Gezogen</th></tr>
            </thead>
            <tbody>
            @forelse($facilities as $f)
                <tr class="border-t border-aurevia-mist/60">
                    <td class="py-1.5">{{ $f->facility_number }}</td>
                    <td class="py-1.5">{{ $f->investorOrganization->name ?? '–' }}</td>
                    <td class="py-1.5 text-right">{{ eur($f->commitment_amount) }}</td>
                    <td class="py-1.5 text-right">{{ eur($f->drawn_amount) }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="py-4 text-center text-aurevia-label-gray">Keine Fazilitäten hinterlegt.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <a href="{{ route('governance.index') }}" class="text-sm text-aurevia-navy border border-aurevia-navy px-3 py-1.5 rounded-md hover:bg-aurevia-pearl inline-block">
        Board Pack &amp; Beschlüsse ansehen
    </a>
</x-app-layout>
