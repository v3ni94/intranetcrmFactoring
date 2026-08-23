<x-app-layout>
    <x-slot name="header">Kreditlinien &amp; Limits</x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white rounded-lg border border-aurevia-mist overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-aurevia-pearl text-[11px] uppercase text-aurevia-label-gray">
                    <tr><th class="text-left p-3">Kunde</th><th class="text-left p-3">Typ</th><th class="text-right p-3">Limit</th><th class="text-right p-3">Auslastung</th></tr>
                </thead>
                <tbody>
                @forelse($lines as $l)
                    <tr class="border-t border-aurevia-mist/60">
                        <td class="p-3">{{ $l->organization->name ?? '–' }}</td>
                        <td class="p-3">{{ $l->line_type }}</td>
                        <td class="p-3 text-right">{{ eur($l->limit_amount) }}</td>
                        <td class="p-3 text-right">{{ pct($l->utilization) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="p-6 text-center text-aurevia-label-gray">Keine Kreditlinien vorhanden.</td></tr>
                @endforelse
                </tbody>
            </table>
            <div class="p-3">{{ $lines->links() }}</div>
        </div>

        <div class="bg-white rounded-lg border border-aurevia-mist p-4">
            <h2 class="text-sm font-semibold text-aurevia-navy mb-3">Neue Kreditlinie</h2>
            <form method="POST" action="{{ route('credit-lines.store') }}" class="space-y-3">
                @csrf
                <select name="organization_id" class="w-full text-sm rounded-md border-aurevia-mist" required>
                    <option value="">Kunde wählen …</option>
                    @foreach($organizations as $org)<option value="{{ $org->id }}">{{ $org->name }}</option>@endforeach
                </select>
                <select name="line_type" class="w-full text-sm rounded-md border-aurevia-mist" required>
                    <option value="ankauf">Ankaufslinie</option>
                    <option value="auszahlung">Auszahlungslinie</option>
                    <option value="debitor">Debitorenlimit</option>
                    <option value="konzentration">Konzentrationsgrenze</option>
                </select>
                <x-text-input type="number" step="0.01" name="limit_amount" placeholder="Limit (EUR)" class="w-full" required />
                <x-text-input name="decision_reason" placeholder="Begründung" class="w-full" />
                <x-primary-button>Anlegen</x-primary-button>
            </form>
        </div>
    </div>
</x-app-layout>
