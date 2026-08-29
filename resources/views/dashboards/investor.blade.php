<x-app-layout>
    <x-slot name="header">{{ __('Ihre Kapitalbeziehung') }} – {{ $org->name }}</x-slot>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
        <x-kpi-card label="{{ __('Zugesagtes Kapital') }}" :value="eur($totalCommitment)" />
        <x-kpi-card label="{{ __('Gezogenes Kapital') }}" :value="eur($totalDrawn)" />
        <x-kpi-card label="{{ __('Ungezogenes Kapital') }}" :value="eur($totalCommitment - $totalDrawn)" />
        <x-kpi-card label="{{ __('Zinsanspruch (aufgelaufen)') }}" :value="eur($accruedInterest)" />
    </div>

    <div class="bg-white rounded-lg border border-aurevia-mist p-4">
        <h2 class="text-sm font-semibold text-aurevia-navy mb-3">{{ __('Meine Fazilitäten') }}</h2>
        <table class="w-full text-sm">
            <thead class="text-[11px] uppercase text-aurevia-label-gray">
                <tr>
                    <th class="text-left pb-2">{{ __('Nummer') }}</th><th class="text-left pb-2">{{ __('Name') }}</th>
                    <th class="text-right pb-2">{{ __('Zusage') }}</th><th class="text-right pb-2">{{ __('Gezogen') }}</th>
                    <th class="text-right pb-2">{{ __('Auslastung') }}</th><th class="text-right pb-2">{{ __('Zinssatz') }}</th>
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
                <tr><td colspan="6" class="py-4 text-center text-aurevia-label-gray">{{ __('Keine Fazilitäten hinterlegt.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{-- Rendite-Einordnung und Anlage-Staffeln (v3.00) --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
        <div class="bg-white rounded-lg border border-aurevia-mist p-4">
            <h2 class="text-sm font-semibold text-aurevia-navy mb-3">{{ __('Ihre Kapitalbeziehung im Überblick') }}</h2>
            <div class="grid grid-cols-2 gap-2 text-sm">
                <div class="text-aurevia-label-gray">{{ __('Erhaltene Zinszahlungen (kumuliert)') }}</div><div class="text-right font-medium">{{ eur($accruedInterest) }}</div>
                <div class="text-aurevia-label-gray">{{ __('Gesamtkapital der Plattform') }}</div><div class="text-right font-medium">{{ eur($platformCommitment) }}</div>
                <div class="text-aurevia-label-gray">{{ __('Ihr Anteil') }}</div><div class="text-right font-medium">{{ $platformCommitment > 0 ? pct(round($totalCommitment / $platformCommitment * 100, 1)) : '–' }}</div>
            </div>
        </div>
        @if($upsellTiers !== [])
        <div class="bg-white rounded-lg border border-aurevia-gold/60 p-4">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-sm font-semibold text-aurevia-navy">{{ __('Mögliche weitere Anlage-Staffeln') }}</h2>
                <span class="text-[10px] uppercase tracking-wide text-aurevia-gold bg-aurevia-navy px-2 py-0.5 rounded">{{ __('Modellrechnung — keine Zusage') }}</span>
            </div>
            <table class="w-full text-sm">
                <thead class="text-[11px] uppercase text-aurevia-label-gray">
                    <tr><th class="text-left pb-2">{{ __('Zusätzliche Zusage') }}</th><th class="text-right pb-2">{{ __('Kalkulatorisch mtl. (:percent % Modellmarge)', ['percent' => number_format($modelMargin, 1, ',', '.')]) }}</th></tr>
                </thead>
                <tbody>
                @foreach($upsellTiers as $tier)
                    <tr class="border-t border-aurevia-mist/60">
                        <td class="py-1.5">{{ eur($tier['amount']) }}</td>
                        <td class="py-1.5 text-right">{{ eur($tier['model_monthly']) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <p class="text-[11px] text-aurevia-label-gray mt-3">
                {{ __('Reine Modellrechnung auf Basis einer kalkulatorischen Monatsmarge. Keine Prognose, keine Zusage, keine Anlageberatung und kein Angebot. Maßgeblich sind ausschließlich die vertraglichen Vereinbarungen. Bei Interesse sprechen Sie Ihren Ansprechpartner an oder erstellen Sie ein Support-Ticket.') }}
            </p>
        </div>
        @endif
    </div>

    <p class="text-[11px] text-aurevia-label-gray mt-4">
        {{ __('Detailtiefe gemäß Vertrag: aggregiert / Kundenebene / pseudonymisierte Forderungsebene. Es werden ausschließlich freigegebene Kennzahlen angezeigt; Patientendaten werden nicht dargestellt.') }}
    </p>
</x-app-layout>
