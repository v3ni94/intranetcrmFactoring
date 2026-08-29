<x-app-layout>
    <x-slot name="header">{{ __('Risiko-Dashboard') }}</x-slot>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <x-kpi-card label="{{ __('Offene KYC/KYB-Fälle') }}" :value="$openKyc" tone="{{ $openKyc > 0 ? 'warn' : 'good' }}" />
        <x-kpi-card label="{{ __('Kunden Risikoklasse Hoch') }}" :value="$watchlistOrgs" tone="{{ $watchlistOrgs > 0 ? 'warn' : 'good' }}" />
        <x-kpi-card label="{{ __('Überfälligkeitsquote') }}" :value="pct($overdueRatio)" formula="{{ __('Überfälliger offener Betrag / gesamter offener Betrag') }}" />
        <x-kpi-card label="{{ __('Top-10-Konzentration') }}" :value="pct($top10)" formula="{{ __('Exposure der zehn größten Risikoeinheiten / Gesamt-Exposure') }}" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg border border-aurevia-mist p-4">
            <h2 class="text-sm font-semibold text-aurevia-navy mb-3">{{ __('Auslastung Kreditlinien (Top 10)') }}</h2>
            <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-[11px] uppercase text-aurevia-label-gray">
                    <tr><th class="text-left pb-2">{{ __('Linie') }}</th><th class="text-right pb-2">{{ __('Auslastung') }}</th></tr>
                </thead>
                <tbody>
                @forelse($utilizedLines as $row)
                    <tr class="border-t border-aurevia-mist/60">
                        <td class="py-1.5">#{{ $row['line']->id }} · {{ $row['line']->line_type }}</td>
                        <td class="py-1.5 text-right">{{ pct($row['utilization']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="2" class="py-4 text-center text-aurevia-label-gray">{{ __('Keine aktiven Linien.') }}</td></tr>
                @endforelse
                </tbody>
            </table>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-aurevia-mist p-4">
            <h2 class="text-sm font-semibold text-aurevia-navy mb-3">{{ __('Altersstruktur offener Forderungen') }}</h2>
            {{-- v3.03: Altersstruktur zusaetzlich grafisch --}}
            <x-bar-chart chart-id="risiko-ageing" :labels="array_map(fn ($b) => $b.' '.__('Tage'), array_keys($ageing))" :values="array_values($ageing)" format="eur" :height="180" />
            <div class="overflow-x-auto">
            <table class="w-full text-sm mt-3">
                @foreach($ageing as $bucket => $amount)
                    <tr class="border-b border-aurevia-mist/60 last:border-0">
                        <td class="py-1.5">{{ $bucket }} {{ __('Tage') }}</td>
                        <td class="py-1.5 text-right font-medium">{{ eur($amount) }}</td>
                    </tr>
                @endforeach
            </table>
            </div>

            <h2 class="text-sm font-semibold text-aurevia-navy mt-6 mb-3">{{ __('Covenant-Warnungen') }}</h2>
            <ul class="text-sm space-y-1">
                @forelse($covenantWarnings as $w)
                    <li class="flex justify-between border-b border-aurevia-mist/60 py-1">
                        <span>{{ __('Fazilität') }} #{{ $w->facility_id }} · {{ $w->covenant_status }}</span>
                        <span class="text-aurevia-label-gray">{{ dmy($w->event_date) }}</span>
                    </li>
                @empty
                    <li class="text-aurevia-label-gray">{{ __('Keine Covenant-Warnungen.') }}</li>
                @endforelse
            </ul>
        </div>
    </div>
</x-app-layout>
