<x-app-layout>
    <x-slot name="header">{{ __('Forderung') }} {{ $receivable->receivable_number }}</x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-lg border border-aurevia-mist p-6">
                <div class="flex items-center justify-between mb-4">
                    <span class="inline-block text-xs px-2 py-1 rounded bg-aurevia-pearl text-aurevia-navy font-medium">{{ $receivable->statusLabel() }}</span>
                    <span class="text-sm text-aurevia-label-gray">{{ __('Kunde:') }} {{ $receivable->organization->name ?? '–' }}</span>
                </div>
                <div class="grid grid-cols-2 gap-2 text-sm mb-4">
                    <div class="text-aurevia-label-gray">{{ __('Rechnungsnummer') }}</div><div class="text-right">{{ $receivable->invoice_number }}</div>
                    <div class="text-aurevia-label-gray">{{ __('Rechnungsdatum') }}</div><div class="text-right">{{ dmy($receivable->invoice_date) }}</div>
                    <div class="text-aurevia-label-gray">{{ __('Fälligkeit') }}</div><div class="text-right">{{ dmy($receivable->due_date) }}</div>
                    <div class="text-aurevia-label-gray">{{ __('Rechnungsbetrag') }}</div><div class="text-right font-semibold">{{ eur($receivable->invoice_amount) }}</div>
                    <div class="text-aurevia-label-gray">{{ __('Vertrag') }}</div><div class="text-right">{{ $receivable->contract->contract_number ?? '–' }}</div>
                </div>

                @if($receivable->rejection_reason)
                    <div class="bg-amber-50 border border-amber-200 text-amber-800 text-sm rounded-md p-3 mb-4">
                        <strong>{{ __('Regel') }} „{{ $receivable->triggered_rule ?? 'manuell' }}“:</strong> {{ $receivable->rejection_reason }}
                    </div>
                @endif

                <div class="flex flex-wrap gap-2">
                    @if($receivable->status === 'eingereicht')
                        <form method="POST" action="{{ route('receivables.formal-check', $receivable) }}">
                            @csrf
                            <button class="text-sm text-white bg-aurevia-navy px-3 py-1.5 rounded-md hover:bg-aurevia-navy/90">{{ __('Formale Prüfung durchführen') }}</button>
                        </form>
                    @endif
                    @if(in_array($receivable->status, ['formale_pruefung', 'rueckfrage']))
                        <form method="POST" action="{{ route('receivables.risk-check', $receivable) }}">
                            @csrf
                            <button class="text-sm text-white bg-aurevia-navy px-3 py-1.5 rounded-md hover:bg-aurevia-navy/90">{{ __('Risiko-/Limitprüfung ausführen') }}</button>
                        </form>
                    @endif
                    @if(!in_array($receivable->status, ['abgelehnt', 'zurueckgezogen', 'bezahlt', 'abgerechnet', 'zweitvotum_marktfolge', 'zweitvotum_vorstand']))
                        <form method="POST" action="{{ route('receivables.reject', $receivable) }}" onsubmit="return confirm('{{ __('Forderung wirklich ablehnen?') }}');">
                            @csrf
                            <input type="hidden" name="reason" value="Manuell abgelehnt durch Sachbearbeitung">
                            <button class="text-sm text-red-700 border border-red-300 px-3 py-1.5 rounded-md hover:bg-red-50">{{ __('Ablehnen') }}</button>
                        </form>
                    @endif

                    {{-- Eskalation nach Markt/Marktfolge-Prinzip (MaRisk, v3.00) --}}
                    @if(in_array($receivable->status, ['abgelehnt', 'rueckfrage']))
                        <form method="POST" action="{{ route('receivables.request-second-vote', $receivable) }}" class="flex items-center gap-2">
                            @csrf
                            <input name="reason" required placeholder="{{ __('Begründung für Zweitvotum') }}" class="text-sm rounded-md border-aurevia-mist" />
                            <button class="text-sm text-aurevia-navy border border-aurevia-navy px-3 py-1.5 rounded-md hover:bg-aurevia-pearl whitespace-nowrap">{{ __('Zweitvotum Marktfolge anfordern') }}</button>
                        </form>
                    @endif
                    @if($receivable->status === 'zweitvotum_marktfolge' && auth()->user()->hasAnyRole(['kredit_risiko', 'geschaeftsleitung', 'superadmin_demo']))
                        <form method="POST" action="{{ route('receivables.market-followup-vote', $receivable) }}" class="flex flex-wrap items-center gap-2">
                            @csrf
                            <input name="reason" required placeholder="{{ __('Begründung (Pflicht)') }}" class="text-sm rounded-md border-aurevia-mist" />
                            <button name="decision" value="freigeben" class="text-sm text-white bg-emerald-700 px-3 py-1.5 rounded-md hover:bg-emerald-800">{{ __('Marktfolge: Freigeben') }}</button>
                            <button name="decision" value="ablehnen" class="text-sm text-red-700 border border-red-300 px-3 py-1.5 rounded-md hover:bg-red-50">{{ __('Marktfolge: Ablehnen → Vorstand') }}</button>
                        </form>
                    @endif
                    @if($receivable->status === 'zweitvotum_vorstand' && auth()->user()->hasAnyRole(['geschaeftsleitung', 'superadmin_demo']))
                        <form method="POST" action="{{ route('receivables.board-vote', $receivable) }}" class="flex flex-wrap items-center gap-2">
                            @csrf
                            <input name="reason" required placeholder="{{ __('Begründung (Pflicht)') }}" class="text-sm rounded-md border-aurevia-mist" />
                            <button name="decision" value="freigeben" class="text-sm text-white bg-emerald-700 px-3 py-1.5 rounded-md hover:bg-emerald-800">{{ __('Vorstand: Freigeben') }}</button>
                            <button name="decision" value="ablehnen" class="text-sm text-white bg-red-700 px-3 py-1.5 rounded-md hover:bg-red-800">{{ __('Vorstand: Endgültig ablehnen') }}</button>
                        </form>
                    @endif
                    @if($receivable->status === 'freigegeben' && !$receivable->purchase)
                        <form method="POST" action="{{ route('purchases.calculate', $receivable) }}">
                            @csrf
                            <button class="text-sm text-white bg-aurevia-gold px-3 py-1.5 rounded-md hover:opacity-90">{{ __('Ankauf berechnen') }}</button>
                        </form>
                    @endif
                    @if($receivable->status === 'bezahlt')
                        <form method="POST" action="{{ route('payments.settle', $receivable) }}">
                            @csrf
                            <button class="text-sm text-white bg-emerald-700 px-3 py-1.5 rounded-md hover:bg-emerald-800">{{ __('Abrechnen & Reserve freigeben') }}</button>
                        </form>
                    @endif
                </div>
            </div>

            @if($receivable->purchase)
            <div class="bg-white rounded-lg border border-aurevia-mist p-6">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-aurevia-navy">{{ __('Ankaufsberechnung') }}</h3>
                    <span class="text-xs px-2 py-1 rounded bg-aurevia-pearl">{{ ucfirst($receivable->purchase->status) }}</span>
                </div>
                <div class="grid grid-cols-2 gap-2 text-sm mb-3">
                    <div class="text-aurevia-label-gray">{{ __('Nominalbetrag') }}</div><div class="text-right">{{ eur($receivable->purchase->nominal_amount) }}</div>
                    <div class="text-aurevia-label-gray">{{ __('Auszahlung (nach Gebühr/Zins)') }}</div><div class="text-right font-semibold text-aurevia-navy">{{ eur($receivable->purchase->immediate_payout_amount) }}</div>
                    <div class="text-aurevia-label-gray">{{ __('Sicherheitseinbehalt') }}</div><div class="text-right">{{ eur($receivable->purchase->reserve_amount) }}</div>
                    <div class="text-aurevia-label-gray">{{ __('Factoringgebühr') }}</div><div class="text-right">{{ eur($receivable->purchase->factoring_fee_amount) }}</div>
                    <div class="text-aurevia-label-gray">{{ __('Erste Freigabe') }}</div><div class="text-right">{{ __('Nutzer') }} #{{ $receivable->purchase->approved_by_first }}</div>
                    <div class="text-aurevia-label-gray">{{ __('Zweite Freigabe') }}</div><div class="text-right">{{ $receivable->purchase->approved_by_second ? __('Nutzer').' #'.$receivable->purchase->approved_by_second : __('ausstehend') }}</div>
                </div>
                @if($receivable->purchase->needsSecondApproval())
                    <form method="POST" action="{{ route('purchases.approve-second', $receivable->purchase) }}">
                        @csrf
                        <button class="text-sm text-white bg-aurevia-navy px-3 py-1.5 rounded-md hover:bg-aurevia-navy/90">{{ __('Zweite Freigabe erteilen (Vier-Augen)') }}</button>
                    </form>
                @endif
            </div>
            @endif
        </div>

        <div class="bg-white rounded-lg border border-aurevia-mist p-6">
            <h3 class="text-sm font-semibold text-aurevia-navy mb-3">{{ __('Debitor') }}</h3>
            <p class="text-sm">{{ $receivable->debtorOrganization->name ?? $receivable->debtor_pseudonym_id ?? __('Privat, pseudonymisiert') }}</p>

            <h3 class="text-sm font-semibold text-aurevia-navy mt-6 mb-3">{{ __('Zahlungen') }}</h3>
            @forelse($receivable->payments as $p)
                <div class="text-sm flex justify-between border-b border-aurevia-mist/60 py-1">
                    <span>{{ $p->type }}</span><span>{{ eur($p->amount) }}</span>
                </div>
            @empty
                <p class="text-sm text-aurevia-label-gray">{{ __('Noch keine Zahlung zugeordnet.') }}</p>
            @endforelse
        </div>
    </div>
</x-app-layout>
