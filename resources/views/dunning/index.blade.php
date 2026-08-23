<x-app-layout>
    <x-slot name="header">Mahnwesen &amp; Streitfälle</x-slot>

    @if($overdueReceivables->isNotEmpty())
    <div class="bg-white rounded-lg border border-aurevia-mist p-6 mb-6">
        <h2 class="text-sm font-semibold text-aurevia-navy mb-3">Überfällige Forderungen ohne offenen Fall</h2>
        @foreach($overdueReceivables as $r)
        <form method="POST" action="{{ route('dunning.store') }}" class="flex items-center gap-3 py-2 border-t border-aurevia-mist/60 text-sm">
            @csrf
            <input type="hidden" name="receivable_id" value="{{ $r->id }}">
            <span class="flex-1">{{ $r->receivable_number }} · {{ $r->organization->name ?? '–' }} · {{ eur($r->invoice_amount) }}</span>
            <select name="case_type" class="text-sm rounded-md border-aurevia-mist">
                <option value="mahnung">Mahnung</option>
                <option value="streitfall">Streitfall</option>
                <option value="rueckgriff">Rückgriff</option>
                <option value="ausfall">Ausfall</option>
            </select>
            <button class="text-aurevia-navy hover:underline">Fall anlegen</button>
        </form>
        @endforeach
    </div>
    @endif

    <div class="bg-white rounded-lg border border-aurevia-mist overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-aurevia-pearl text-[11px] uppercase text-aurevia-label-gray">
                <tr>
                    <th class="text-left p-3">Forderung</th><th class="text-left p-3">Typ</th>
                    <th class="text-right p-3">Offener Betrag</th><th class="text-left p-3">Status</th><th class="p-3"></th>
                </tr>
            </thead>
            <tbody>
            @forelse($cases as $c)
                <tr class="border-t border-aurevia-mist/60">
                    <td class="p-3">{{ $c->receivable->receivable_number }}</td>
                    <td class="p-3 capitalize">{{ $c->case_type }}</td>
                    <td class="p-3 text-right">{{ eur($c->open_amount) }}</td>
                    <td class="p-3 capitalize">{{ str_replace('_',' ', $c->status) }}</td>
                    <td class="p-3 text-right space-x-2">
                        @if(!in_array($c->status, ['geschlossen', 'inkasso']))
                        <form method="POST" action="{{ route('dunning.hand-over', $c) }}" class="inline">
                            @csrf
                            <button class="text-aurevia-navy hover:underline">An Inkasso übergeben (Demo)</button>
                        </form>
                        @endif
                        @if($c->status !== 'geschlossen')
                        <form method="POST" action="{{ route('dunning.close', $c) }}" class="inline">
                            @csrf
                            <button class="text-aurevia-navy hover:underline">Schließen</button>
                        </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="p-6 text-center text-aurevia-label-gray">Keine Fälle vorhanden.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $cases->links() }}</div>
</x-app-layout>
