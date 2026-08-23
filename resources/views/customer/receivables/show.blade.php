<x-app-layout>
    <x-slot name="header">Forderung {{ $receivable->receivable_number }}</x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white rounded-lg border border-aurevia-mist p-6">
            <div class="flex items-center justify-between mb-4">
                <span class="inline-block text-xs px-2 py-1 rounded bg-aurevia-pearl text-aurevia-navy font-medium">{{ $receivable->statusLabel() }}</span>
                <span class="text-sm text-aurevia-label-gray">Rechnung {{ $receivable->invoice_number }}</span>
            </div>
            <div class="grid grid-cols-2 gap-2 text-sm mb-6">
                <div class="text-aurevia-label-gray">Rechnungsdatum</div><div class="text-right">{{ dmy($receivable->invoice_date) }}</div>
                <div class="text-aurevia-label-gray">Fälligkeit</div><div class="text-right">{{ dmy($receivable->due_date) }}</div>
                <div class="text-aurevia-label-gray">Rechnungsbetrag</div><div class="text-right font-semibold">{{ eur($receivable->invoice_amount) }}</div>
            </div>

            @if($receivable->rejection_reason)
                <div class="bg-amber-50 border border-amber-200 text-amber-800 text-sm rounded-md p-3 mb-4">
                    <strong>Rückfrage/Hinweis:</strong> {{ $receivable->rejection_reason }}
                </div>
            @endif

            @if($receivable->purchase)
                <h3 class="text-sm font-semibold text-aurevia-navy mb-2">Ankaufsberechnung</h3>
                <div class="grid grid-cols-2 gap-2 text-sm">
                    <div class="text-aurevia-label-gray">Ankauffähiger Betrag</div><div class="text-right">{{ eur($receivable->purchase->purchasable_amount) }}</div>
                    <div class="text-aurevia-label-gray">Auszahlung</div><div class="text-right font-semibold text-aurevia-navy">{{ eur($receivable->purchase->immediate_payout_amount) }}</div>
                    <div class="text-aurevia-label-gray">Sicherheitseinbehalt</div><div class="text-right">{{ eur($receivable->purchase->reserve_amount) }}</div>
                    <div class="text-aurevia-label-gray">Gebühr</div><div class="text-right">{{ eur($receivable->purchase->factoring_fee_amount) }}</div>
                    <div class="text-aurevia-label-gray">Voraussichtliche Zinsen</div><div class="text-right">{{ eur($receivable->purchase->expected_interest_amount) }}</div>
                </div>
            @endif
        </div>

        <div class="bg-white rounded-lg border border-aurevia-mist p-6">
            <h3 class="text-sm font-semibold text-aurevia-navy mb-3">Status</h3>
            <p class="text-sm">Aktueller Status: <strong>{{ $receivable->statusLabel() }}</strong></p>
            <p class="text-[11px] text-aurevia-label-gray mt-3">Der vollständige Audit-Trail ist für interne Rollen unter „Audit &amp; Freigaben“ einsehbar.</p>
        </div>
    </div>
</x-app-layout>
