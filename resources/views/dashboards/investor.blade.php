<x-app-layout>
    <x-slot name="header">Ihre Kapitalbeziehung – {{ $org->name }}</x-slot>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
        <x-kpi-card label="Zugesagtes Kapital" :value="eur($totalCommitment)" />
        <x-kpi-card label="Gezogenes Kapital" :value="eur($totalDrawn)" />
        <x-kpi-card label="Ungezogenes Kapital" :value="eur($totalCommitment - $totalDrawn)" />
        <x-kpi-card label="Zinsanspruch (aufgelaufen)" :value="eur($accruedInterest)" />
    </div>

    <div class="bg-white rounded-lg border border-aurevia-mist p-4">
        <h2 class="text-sm font-semibold text-aurevia-navy mb-3">Meine Fazilitäten</h2>
        <table class="w-full text-sm">
            <thead class="text-[11px] uppercase text-aurevia-label-gray">
                <tr>
                    <th class="text-left pb-2">Nummer</th><th class="text-left pb-2">Name</th>
                    <th class="text-right pb-2">Zusage</th><th class="text-right pb-2">Gezogen</th>
                    <th class="text-right pb-2">Auslastung</th><th class="text-right pb-2">Zinssatz</th>
                </tr>
            </thead>
            <tbody>
            @forelse($facilities as $f)
                <tr class="border-t border-aurevia-mist/60">
                    <td class="py-1.5">{{ $f->facility_number }}</td>
                    <td class="py-1.5">{{ $f->name }}</td>
                    <td class="py-1.5 text-right">{{ eur($f->commitment_amount) }}</td>
                    <td class="py-1.5 text-right">{{ eur($f->drawn_amount) }}</td>
                    <td class="py-1.5 text-right">{{ pct($f->utilization) }}</td>
                    <td class="py-1.5 text-right">{{ pct($f->interest_rate_percent) }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="py-4 text-center text-aurevia-label-gray">Keine Fazilitäten hinterlegt.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <p class="text-[11px] text-aurevia-label-gray mt-4">
        Detailtiefe gemäß Vertrag: aggregiert / Kundenebene / pseudonymisierte Forderungsebene. Es werden ausschließlich freigegebene
        Kennzahlen angezeigt; Patientendaten werden nicht dargestellt.
    </p>
</x-app-layout>
