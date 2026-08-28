<x-app-layout>
    <x-slot name="header">Kreditlinien &amp; Limits</x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white rounded-lg border border-aurevia-mist overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-aurevia-pearl text-[11px] uppercase text-aurevia-label-gray">
                    <tr><th class="text-left p-3">Kunde</th><th class="text-left p-3">Typ</th><th class="text-right p-3">Limit</th><th class="text-right p-3">Auslastung</th><th class="text-left p-3">Versicherung</th></tr>
                </thead>
                <tbody>
                @forelse($lines as $l)
                    <tr class="border-t border-aurevia-mist/60">
                        <td class="p-3">{{ $l->organization->name ?? '–' }}</td>
                        <td class="p-3">{{ $l->line_type }}</td>
                        <td class="p-3 text-right">{{ eur($l->limit_amount) }}</td>
                        <td class="p-3 text-right">{{ pct($l->utilization) }}</td>
                        <td class="p-3">
                            @if((float) $l->limit_amount > (float) config('aurevia.insurance_threshold') && $l->insurance_status === 'nicht_versichert')
                                <span class="text-xs px-2 py-0.5 rounded bg-red-100 text-red-800">Klumpenrisiko — Versicherung prüfen</span>
                            @elseif($l->insurance_status === 'versichert')
                                <span class="text-xs px-2 py-0.5 rounded bg-emerald-100 text-emerald-800">versichert{{ $l->insured_amount ? ' ('.eur($l->insured_amount).')' : '' }}</span>
                            @elseif($l->insurance_status === 'beantragt')
                                <span class="text-xs px-2 py-0.5 rounded bg-blue-100 text-blue-800">beantragt</span>
                            @else
                                <span class="text-xs text-aurevia-label-gray">–</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="p-6 text-center text-aurevia-label-gray">Keine Kreditlinien vorhanden.</td></tr>
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
