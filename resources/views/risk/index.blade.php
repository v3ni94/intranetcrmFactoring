<x-app-layout>
    <x-slot name="header">{{ __('Risiko & Compliance') }}</x-slot>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
        <x-kpi-card label="{{ __('Überfälligkeitsquote') }}" :value="pct($overdueRatio)" />
        <x-kpi-card label="{{ __('Top-10-Konzentration') }}" :value="pct($top10)" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg border border-aurevia-mist p-4">
            <h2 class="text-sm font-semibold text-aurevia-navy mb-3">{{ __('KYC/KYB-Fälle') }}</h2>
            <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-[11px] uppercase text-aurevia-label-gray"><tr><th class="text-left pb-2">{{ __('Kunde') }}</th><th class="text-left pb-2">{{ __('Typ') }}</th><th class="text-left pb-2">{{ __('Ergebnis') }}</th></tr></thead>
                <tbody>
                @forelse($kycCases as $k)
                    <tr class="border-t border-aurevia-mist/60"><td class="py-1.5">{{ $k->organization->name ?? '–' }}</td><td class="py-1.5">{{ $k->case_type }}</td><td class="py-1.5">{{ $k->result }}</td></tr>
                @empty
                    <tr><td colspan="3" class="py-4 text-center text-aurevia-label-gray">{{ __('Keine Fälle vorhanden.') }}</td></tr>
                @endforelse
                </tbody>
            </table>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-aurevia-mist p-4">
            <h2 class="text-sm font-semibold text-aurevia-navy mb-3">{{ __('Watchlist (Risikoklasse Hoch)') }}</h2>
            <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-[11px] uppercase text-aurevia-label-gray"><tr><th class="text-left pb-2">{{ __('Kunde') }}</th><th class="text-left pb-2">{{ __('Ort') }}</th></tr></thead>
                <tbody>
                @forelse($watchlist as $w)
                    <tr class="border-t border-aurevia-mist/60"><td class="py-1.5">{{ $w->name }}</td><td class="py-1.5">{{ $w->city }}</td></tr>
                @empty
                    <tr><td colspan="2" class="py-4 text-center text-aurevia-label-gray">{{ __('Keine Watchlist-Einträge.') }}</td></tr>
                @endforelse
                </tbody>
            </table>
            </div>
        </div>
    </div>
</x-app-layout>
