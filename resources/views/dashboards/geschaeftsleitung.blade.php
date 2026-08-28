<x-app-layout>
    <x-slot name="header">Geschäftsleitung / Vorstand</x-slot>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <x-kpi-card label="Angekauft (Monat)" :value="eur($purchasedMonth)" />
        <x-kpi-card label="Angekauft (YTD)" :value="eur($purchasedYtd)" />
        <x-kpi-card label="Angekauft (12 Monate)" :value="eur($purchasedTwelveMonths)" />
        <x-kpi-card label="Ausstehendes Portfolio" :value="eur($outstandingPortfolio)" />
        <x-kpi-card label="Bruttoertrag" :value="eur($grossRevenue)" formula="Factoringgebühren + Servicegebühren + Zinserträge" />
        <x-kpi-card label="Refinanzierungskosten" :value="eur($refinancingCost)" />
        <x-kpi-card label="Deckungsbeitrag" :value="eur($contributionMargin)" formula="Bruttoertrag − Refinanzierungskosten − realisierte Kreditverluste" />
        <x-kpi-card label="Verwässerungsquote" :value="pct($dilution)" />
        <x-kpi-card label="Überfälligkeitsquote" :value="pct($overdueRatio)" />
        <x-kpi-card label="Top-10-Konzentration" :value="pct($top10)" />
        <x-kpi-card label="DSO" :value="$dso.' Tage'" formula="Gewichtete durchschnittliche Tage zwischen Rechnungsdatum und Zahlung" />
    </div>

    <div class="bg-white rounded-lg border border-aurevia-mist p-4 mb-6">
        <h2 class="text-sm font-semibold text-aurevia-navy mb-3">{{ __('Altersstruktur des Portfolios') }}</h2>
        <x-bar-chart chart-id="ageingChart"
            :labels="collect($ageing)->keys()->map(fn ($b) => $b.' '.__('Tage'))->all()"
            :values="array_values($ageing)" />
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm mt-4">
            @foreach($ageing as $bucket => $amount)
                <div class="text-center">
                    <div class="text-aurevia-label-gray text-[11px] uppercase">{{ $bucket }} {{ __('Tage') }}</div>
                    <div class="text-lg font-semibold text-aurevia-navy">{{ eur($amount) }}</div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="bg-white rounded-lg border border-aurevia-mist p-4 mb-6">
        <h2 class="text-sm font-semibold text-aurevia-navy mb-3">{{ __('Ankaufsvolumen (letzte 6 Monate)') }}</h2>
        <x-bar-chart chart-id="purchasesChart"
            :labels="$monthlyPurchases->pluck('label')->all()"
            :values="$monthlyPurchases->pluck('value')->all()" />
    </div>

    <div class="bg-white rounded-lg border border-aurevia-mist p-4">
        <div class="flex items-center justify-between mb-1">
            <h2 class="text-sm font-semibold text-aurevia-navy">Finanzszenarien (Hypothese, Finanzmodell V1 vom 19.08.2026)</h2>
            <span class="text-[11px] uppercase tracking-wide text-aurevia-gold bg-aurevia-navy px-2 py-0.5 rounded">Hypothese – nicht beschlossen</span>
        </div>
        <table class="w-full text-sm mt-3">
            <thead class="text-[11px] uppercase text-aurevia-label-gray">
                <tr>
                    <th class="text-left pb-2">Parameter</th>
                    @foreach($scenarios as $s)<th class="text-right pb-2">{{ $s->label }}</th>@endforeach
                </tr>
            </thead>
            <tbody>
                <tr class="border-t border-aurevia-mist/60"><td class="py-1.5">Portfolio Jahresende Jahr 1</td>@foreach($scenarios as $s)<td class="py-1.5 text-right">{{ eur($s->portfolio_year1_eur) }}</td>@endforeach</tr>
                <tr class="border-t border-aurevia-mist/60"><td class="py-1.5">Wachstum p.a.</td>@foreach($scenarios as $s)<td class="py-1.5 text-right">{{ pct($s->growth_yoy_percent) }}</td>@endforeach</tr>
                <tr class="border-t border-aurevia-mist/60"><td class="py-1.5">Factoringgebühr</td>@foreach($scenarios as $s)<td class="py-1.5 text-right">{{ pct($s->factoring_fee_percent) }}</td>@endforeach</tr>
                <tr class="border-t border-aurevia-mist/60"><td class="py-1.5">DSO</td>@foreach($scenarios as $s)<td class="py-1.5 text-right">{{ $s->dso_days }} Tage</td>@endforeach</tr>
                <tr class="border-t border-aurevia-mist/60"><td class="py-1.5">Advance Rate</td>@foreach($scenarios as $s)<td class="py-1.5 text-right">{{ pct($s->advance_rate_percent) }}</td>@endforeach</tr>
                <tr class="border-t border-aurevia-mist/60"><td class="py-1.5">Fremdkapitalzins</td>@foreach($scenarios as $s)<td class="py-1.5 text-right">{{ pct($s->debt_interest_percent) }}</td>@endforeach</tr>
                <tr class="border-t border-aurevia-mist/60"><td class="py-1.5">Kundenzins</td>@foreach($scenarios as $s)<td class="py-1.5 text-right">{{ pct($s->customer_interest_percent) }}</td>@endforeach</tr>
            </tbody>
        </table>
        <p class="text-[11px] text-aurevia-label-gray mt-3">
            Hinweis lt. Masterprompt Abschnitt 21.1: Das Base-Modell erzeugt in der vorliegenden Ausgangsbelegung bis Jahr 4 kumulierte
            Anlaufverluste von rund 3,0 Mio. EUR und übersteigt damit das angenommene Eigenkapital von 2,0 Mio. EUR. Reine Hypothese,
            keine Zusage, keine Prognose – siehe Projekt &amp; Beschlüsse.
        </p>
    </div>
</x-app-layout>
