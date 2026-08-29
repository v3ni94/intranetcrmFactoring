<x-app-layout>
    <x-slot name="header">{{ __('Willkommen') }}, {{ $org->name }}</x-slot>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
        <x-kpi-card label="{{ __('Heute verfügbar') }}" :value="eur($available)" tone="good"
            formula="{{ __('Auszahlungslinie − bereits genutzter Betrag der aktiven Auszahlungslinie') }}" />
        <x-kpi-card label="{{ __('Bereits ausgezahlt (Monat)') }}" :value="eur($payoutMonth)"
            :period="__('seit :date', ['date' => now()->startOfMonth()->format('d.m.Y')])" formula="{{ __('Summe bestätigter Auszahlungen im laufenden Monat') }}" />
        <x-kpi-card label="{{ __('Bereits ausgezahlt (Jahr)') }}" :value="eur($payoutYear)" period="{{ __('laufendes Kalenderjahr') }}" />
        <x-kpi-card label="{{ __('In Prüfung') }}" :value="__(':count Rechnung(en) · :amount', ['count' => $review['count'], 'amount' => eur($review['amount'])])" tone="neutral" />
        <x-kpi-card label="{{ __('Handlung erforderlich') }}" :value="$actionRequired" tone="{{ $actionRequired > 0 ? 'warn' : 'good' }}" />
        <x-kpi-card label="{{ __('Ihre Kosten') }}" :value="__('Gebühren :fees · Zinsen :interest', ['fees' => eur($costs['fees']), 'interest' => eur($costs['interest'])])" />
    </div>

    <div class="bg-white rounded-lg border border-aurevia-mist p-4 mb-6">
        <h2 class="text-sm font-semibold text-aurevia-navy mb-1">{{ __('Nächster Schritt') }}</h2>
        @if($actionRequired > 0)
            <p class="text-sm">{!! __('Sie haben :count Rechnung(en) mit Rückfrage oder Ablehnung. Bitte prüfen Sie diese in ":place".', ['count' => '<strong>'.$actionRequired.'</strong>', 'place' => __('Meine Forderungen')]) !!}</p>
        @else
            <p class="text-sm text-emerald-700">{{ __('Alles erledigt – aktuell ist keine Handlung Ihrerseits notwendig.') }}</p>
        @endif
        <a href="{{ route('customer.receivables.create') }}" class="inline-block mt-3 text-sm text-white bg-aurevia-navy px-3 py-1.5 rounded-md hover:bg-aurevia-navy/90">
            {{ __('Neue Forderung einreichen') }}
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg border border-aurevia-mist p-4">
            <h2 class="text-sm font-semibold text-aurevia-navy mb-3">{{ __('Status-Trichter Ihrer Forderungen') }}</h2>
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
            <h2 class="text-sm font-semibold text-aurevia-navy mb-3">{{ __('Zuletzt eingereicht') }}</h2>
            <table class="w-full text-sm">
                <thead class="text-[11px] uppercase text-aurevia-label-gray">
                    <tr><th class="text-left pb-2">{{ __('Nummer') }}</th><th class="text-left pb-2">{{ __('Status') }}</th><th class="text-right pb-2">{{ __('Betrag') }}</th></tr>
                </thead>
                <tbody>
                @forelse($recent as $r)
                    <tr class="border-t border-aurevia-mist/60">
                        <td class="py-1.5"><a class="text-aurevia-navy hover:underline" href="{{ route('customer.receivables.show', $r) }}">{{ $r->receivable_number }}</a></td>
                        <td class="py-1.5">{{ $r->statusLabel() }}</td>
                        <td class="py-1.5 text-right">{{ eur($r->invoice_amount) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="py-4 text-center text-aurevia-label-gray">{{ __('Noch keine Forderungen eingereicht.') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
