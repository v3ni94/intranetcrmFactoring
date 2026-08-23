<x-app-layout>
    <x-slot name="header">Willkommen, {{ $org->name }}</x-slot>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
        <x-kpi-card label="Heute verfügbar" :value="eur($available)" tone="good"
            formula="Auszahlungslinie − bereits genutzter Betrag der aktiven Auszahlungslinie" />
        <x-kpi-card label="Bereits ausgezahlt (Monat)" :value="eur($payoutMonth)"
            :period="'seit '.now()->startOfMonth()->format('d.m.Y')" formula="Summe bestätigter Auszahlungen im laufenden Monat" />
        <x-kpi-card label="Bereits ausgezahlt (Jahr)" :value="eur($payoutYear)" period="laufendes Kalenderjahr" />
        <x-kpi-card label="In Prüfung" :value="$review['count'].' Rechnung(en) · '.eur($review['amount'])" tone="neutral" />
        <x-kpi-card label="Handlung erforderlich" :value="$actionRequired" tone="{{ $actionRequired > 0 ? 'warn' : 'good' }}" />
        <x-kpi-card label="Ihre Kosten" :value="'Gebühren '.eur($costs['fees']).' · Zinsen '.eur($costs['interest'])" />
    </div>

    <div class="bg-white rounded-lg border border-aurevia-mist p-4 mb-6">
        <h2 class="text-sm font-semibold text-aurevia-navy mb-1">Nächster Schritt</h2>
        @if($actionRequired > 0)
            <p class="text-sm">Sie haben <strong>{{ $actionRequired }}</strong> Rechnung(en) mit Rückfrage oder Ablehnung. Bitte prüfen Sie diese in "Meine Forderungen".</p>
        @else
            <p class="text-sm text-emerald-700">Alles erledigt – aktuell ist keine Handlung Ihrerseits notwendig.</p>
        @endif
        <a href="{{ route('customer.receivables.create') }}" class="inline-block mt-3 text-sm text-white bg-aurevia-navy px-3 py-1.5 rounded-md hover:bg-aurevia-navy/90">
            Neue Forderung einreichen
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg border border-aurevia-mist p-4">
            <h2 class="text-sm font-semibold text-aurevia-navy mb-3">Status-Trichter Ihrer Forderungen</h2>
            <table class="w-full text-sm">
                @foreach(\App\Models\Receivable::STATUS_LABELS as $key => $label)
                    @if(isset($funnel[$key]))
                    <tr class="border-b border-aurevia-mist/60 last:border-0">
                        <td class="py-1.5">{{ $label }}</td>
                        <td class="py-1.5 text-right text-aurevia-label-gray">{{ $funnel[$key]->c }}</td>
                        <td class="py-1.5 text-right font-medium">{{ eur($funnel[$key]->amount) }}</td>
                    </tr>
                    @endif
                @endforeach
            </table>
        </div>

        <div class="bg-white rounded-lg border border-aurevia-mist p-4">
            <h2 class="text-sm font-semibold text-aurevia-navy mb-3">Zuletzt eingereicht</h2>
            <table class="w-full text-sm">
                <thead class="text-[11px] uppercase text-aurevia-label-gray">
                    <tr><th class="text-left pb-2">Nummer</th><th class="text-left pb-2">Status</th><th class="text-right pb-2">Betrag</th></tr>
                </thead>
                <tbody>
                @forelse($recent as $r)
                    <tr class="border-t border-aurevia-mist/60">
                        <td class="py-1.5"><a class="text-aurevia-navy hover:underline" href="{{ route('customer.receivables.show', $r) }}">{{ $r->receivable_number }}</a></td>
                        <td class="py-1.5">{{ $r->statusLabel() }}</td>
                        <td class="py-1.5 text-right">{{ eur($r->invoice_amount) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="py-4 text-center text-aurevia-label-gray">Noch keine Forderungen eingereicht.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
