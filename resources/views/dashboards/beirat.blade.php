<x-app-layout>
    <x-slot name="header">{{ __('Beirat / Aufsichtsrat – Read-only') }}</x-slot>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <x-kpi-card label="{{ __('Ausstehendes Portfolio') }}" :value="eur($outstandingPortfolio)" />
        <x-kpi-card label="{{ __('Bruttoertrag') }}" :value="eur($grossRevenue)" />
        <x-kpi-card label="{{ __('Deckungsbeitrag') }}" :value="eur($contributionMargin)" />
        <x-kpi-card label="{{ __('Überfälligkeitsquote') }}" :value="pct($overdueRatio)" />
        <x-kpi-card label="{{ __('Top-10-Konzentration') }}" :value="pct($top10)" />
        <x-kpi-card label="{{ __('Covenant-Warnungen') }}" :value="$covenantWarnings" tone="{{ $covenantWarnings > 0 ? 'warn' : 'good' }}" />
        <x-kpi-card label="{{ __('Neugeschäft (30 Tage)') }}" :value="$newBusinessCount.' '.__('Ankäufe')" />
    </div>

    <div class="bg-white rounded-lg border border-aurevia-mist p-4 mb-6">
        <h2 class="text-sm font-semibold text-aurevia-navy mb-3">{{ __('Investorenlinien') }}</h2>
        <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="text-[11px] uppercase text-aurevia-label-gray">
                <tr><th class="text-left pb-2">{{ __('Fazilität') }}</th><th class="text-left pb-2">{{ __('Investor') }}</th><th class="text-right pb-2">{{ __('Zusage') }}</th><th class="text-right pb-2">{{ __('Gezogen') }}</th></tr>
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
                <tr><td colspan="4" class="py-4 text-center text-aurevia-label-gray">{{ __('Keine Fazilitäten hinterlegt.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
        </div>
    </div>

    {{-- v3.03: wirtschaftliche Entwicklung grafisch --}}
    <div class="bg-white rounded-lg border border-aurevia-mist p-4 mb-6">
        <h2 class="text-sm font-semibold text-aurevia-navy mb-3">{{ __('Ankaufsvolumen je Monat (12 Monate)') }}</h2>
        <x-bar-chart chart-id="beirat-volume" :labels="$volumeLabels" :values="$volumeValues" format="eur" />
    </div>

    <a href="{{ route('governance.index') }}" class="text-sm text-aurevia-navy border border-aurevia-navy px-3 py-1.5 rounded-md hover:bg-aurevia-pearl inline-block">
        {{ __('Board Pack & Beschlüsse ansehen') }}
    </a>
</x-app-layout>
