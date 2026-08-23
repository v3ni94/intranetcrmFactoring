<x-app-layout>
    <x-slot name="header">Forderungen</x-slot>

    <div class="flex flex-wrap gap-2 mb-4 text-xs">
        <a href="{{ route('receivables.index') }}" class="px-2 py-1 rounded {{ !$status ? 'bg-aurevia-navy text-white' : 'bg-white border border-aurevia-mist' }}">Alle</a>
        @foreach(\App\Models\Receivable::STATUS_LABELS as $key => $label)
            <a href="{{ route('receivables.index', ['status' => $key]) }}" class="px-2 py-1 rounded {{ $status === $key ? 'bg-aurevia-navy text-white' : 'bg-white border border-aurevia-mist' }}">
                {{ $label }} ({{ $statusCounts[$key] ?? 0 }})
            </a>
        @endforeach
    </div>

    <div class="bg-white rounded-lg border border-aurevia-mist overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-aurevia-pearl text-[11px] uppercase text-aurevia-label-gray">
                <tr>
                    <th class="text-left p-3">Nummer</th><th class="text-left p-3">Kunde</th>
                    <th class="text-right p-3">Betrag</th><th class="text-left p-3">Status</th><th class="text-left p-3">Fällig</th><th class="p-3"></th>
                </tr>
            </thead>
            <tbody>
            @forelse($receivables as $r)
                <tr class="border-t border-aurevia-mist/60 hover:bg-aurevia-pearl/50">
                    <td class="p-3">{{ $r->receivable_number }}</td>
                    <td class="p-3">{{ $r->organization->name ?? '–' }}</td>
                    <td class="p-3 text-right">{{ eur($r->invoice_amount) }}</td>
                    <td class="p-3">{{ $r->statusLabel() }}</td>
                    <td class="p-3">{{ dmy($r->due_date) }}</td>
                    <td class="p-3 text-right"><a href="{{ route('receivables.show', $r) }}" class="text-aurevia-navy hover:underline">Öffnen</a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="p-6 text-center text-aurevia-label-gray">Keine Forderungen gefunden.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $receivables->links() }}</div>
</x-app-layout>
