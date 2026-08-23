<x-app-layout>
    <x-slot name="header">Ankauf &amp; Auszahlungen</x-slot>

    <div class="bg-white rounded-lg border border-aurevia-mist p-6 mb-6">
        <h2 class="text-sm font-semibold text-aurevia-navy mb-3">Neuen Auszahlungsbatch bilden</h2>
        @if($readyPurchases->isEmpty())
            <p class="text-sm text-aurevia-label-gray">Aktuell keine freigegebenen Ankäufe ohne Auszahlung.</p>
        @else
        <form method="POST" action="{{ route('payouts.store') }}" class="space-y-3">
            @csrf
            <div>
                <x-input-label value="Bankkonto" />
                <select name="bank_account_id" class="mt-1 w-full rounded-md border-aurevia-mist" required>
                    @foreach($bankAccounts as $a)
                        <option value="{{ $a->id }}">{{ $a->account_name }} ({{ $a->bank_name }})</option>
                    @endforeach
                </select>
            </div>
            <table class="w-full text-sm">
                <thead class="text-[11px] uppercase text-aurevia-label-gray">
                    <tr><th class="text-left pb-2"></th><th class="text-left pb-2">Kunde</th><th class="text-right pb-2">Auszahlung</th></tr>
                </thead>
                <tbody>
                @foreach($readyPurchases as $p)
                    <tr class="border-t border-aurevia-mist/60">
                        <td class="py-1.5"><input type="checkbox" name="purchase_ids[]" value="{{ $p->id }}"></td>
                        <td class="py-1.5">{{ $p->receivable->organization->name ?? '–' }}</td>
                        <td class="py-1.5 text-right">{{ eur($p->immediate_payout_amount) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <x-primary-button>Batch erstellen</x-primary-button>
        </form>
        @endif
    </div>

    <div class="bg-white rounded-lg border border-aurevia-mist overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-aurevia-pearl text-[11px] uppercase text-aurevia-label-gray">
                <tr>
                    <th class="text-left p-3">Batch</th><th class="text-left p-3">Bankkonto</th>
                    <th class="text-right p-3">Summe</th><th class="text-left p-3">Status</th><th class="p-3"></th>
                </tr>
            </thead>
            <tbody>
            @forelse($batches as $b)
                <tr class="border-t border-aurevia-mist/60">
                    <td class="p-3">{{ $b->batch_number }}</td>
                    <td class="p-3">{{ $b->bankAccount->account_name }}</td>
                    <td class="p-3 text-right">{{ eur($b->total_amount) }}</td>
                    <td class="p-3">{{ ucfirst(str_replace('_',' ', $b->status)) }}</td>
                    <td class="p-3 text-right space-x-2">
                        @if($b->status === 'erstellt')
                            <form class="inline" method="POST" action="{{ route('payouts.approve-first', $b) }}">@csrf<button class="text-aurevia-navy hover:underline">1. Freigabe</button></form>
                        @endif
                        @if($b->status === 'freigegeben_1')
                            <form class="inline" method="POST" action="{{ route('payouts.approve-second', $b) }}">@csrf<button class="text-aurevia-navy hover:underline">2. Freigabe + SEPA-Export</button></form>
                        @endif
                        @if($b->status === 'angewiesen')
                            <form class="inline" method="POST" action="{{ route('payouts.confirm', $b) }}">@csrf<button class="text-emerald-700 hover:underline">Bankbestätigung (Demo)</button></form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="p-6 text-center text-aurevia-label-gray">Noch keine Auszahlungsbatches.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $batches->links() }}</div>
</x-app-layout>
