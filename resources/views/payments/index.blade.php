<x-app-layout>
    <x-slot name="header">Zahlungseingänge &amp; Abstimmung</x-slot>

    <div class="mb-4">
        <form method="POST" action="{{ route('payments.import-demo') }}">
            @csrf
            <button class="text-sm text-white bg-aurevia-navy px-3 py-1.5 rounded-md hover:bg-aurevia-navy/90">
                Demo-Kontoauszug importieren (camt.053)
            </button>
        </form>
    </div>

    <div class="bg-white rounded-lg border border-aurevia-mist overflow-x-auto mb-6">
        <table class="w-full text-sm">
            <thead class="bg-aurevia-pearl text-[11px] uppercase text-aurevia-label-gray">
                <tr>
                    <th class="text-left p-3">Wertstellung</th><th class="text-left p-3">Verwendungszweck</th>
                    <th class="text-right p-3">Betrag</th><th class="text-left p-3">Vorschlag</th><th class="p-3"></th>
                </tr>
            </thead>
            <tbody>
            @forelse($openTransactions as $t)
                <tr class="border-t border-aurevia-mist/60">
                    <td class="p-3">{{ dmy($t->value_date) }}</td>
                    <td class="p-3">{{ $t->reference }}</td>
                    <td class="p-3 text-right">{{ eur($t->amount) }}</td>
                    <td class="p-3">
                        @if($t->suggestion['receivable'])
                            {{ $t->suggestion['receivable']->receivable_number }} · {{ pct($t->suggestion['confidence']) }} · {{ $t->suggestion['reason'] }}
                        @else
                            <span class="text-aurevia-label-gray">{{ $t->suggestion['reason'] }}</span>
                        @endif
                    </td>
                    <td class="p-3 text-right">
                        @if($t->suggestion['receivable'])
                        <form method="POST" action="{{ route('payments.match', $t) }}">
                            @csrf
                            <input type="hidden" name="receivable_id" value="{{ $t->suggestion['receivable']->id }}">
                            <button class="text-aurevia-navy hover:underline">Bestätigen</button>
                        </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="p-6 text-center text-aurevia-label-gray">Keine offenen Kontobewegungen.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="bg-white rounded-lg border border-aurevia-mist overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-aurevia-pearl text-[11px] uppercase text-aurevia-label-gray">
                <tr><th class="text-left p-3">Wertstellung</th><th class="text-left p-3">Zuordnung</th><th class="text-right p-3">Betrag</th></tr>
            </thead>
            <tbody>
            @forelse($matched as $t)
                <tr class="border-t border-aurevia-mist/60">
                    <td class="p-3">{{ dmy($t->value_date) }}</td>
                    <td class="p-3">{{ $t->payments->first()?->receivable?->receivable_number ?? '–' }}</td>
                    <td class="p-3 text-right">{{ eur($t->amount) }}</td>
                </tr>
            @empty
                <tr><td colspan="3" class="p-6 text-center text-aurevia-label-gray">Noch keine zugeordneten Zahlungen.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</x-app-layout>
