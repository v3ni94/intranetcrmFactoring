<x-app-layout>
    <x-slot name="header">Investoren &amp; Finanzierungsfazilitäten</x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white rounded-lg border border-aurevia-mist overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-aurevia-pearl text-[11px] uppercase text-aurevia-label-gray">
                    <tr><th class="text-left p-3">Nummer</th><th class="text-left p-3">Investor</th><th class="text-right p-3">Zusage</th><th class="text-right p-3">Auslastung</th></tr>
                </thead>
                <tbody>
                @forelse($facilities as $f)
                    <tr class="border-t border-aurevia-mist/60">
                        <td class="p-3">{{ $f->facility_number }}</td>
                        <td class="p-3">{{ $f->investorOrganization->name ?? '–' }}</td>
                        <td class="p-3 text-right">{{ eur($f->commitment_amount) }}</td>
                        <td class="p-3 text-right">{{ pct($f->utilization) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="p-6 text-center text-aurevia-label-gray">Keine Fazilitäten vorhanden.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="bg-white rounded-lg border border-aurevia-mist p-4">
            <h2 class="text-sm font-semibold text-aurevia-navy mb-3">Neue Fazilität</h2>
            <form method="POST" action="{{ route('facilities.store') }}" class="space-y-3">
                @csrf
                <select name="investor_organization_id" class="w-full text-sm rounded-md border-aurevia-mist" required>
                    <option value="">Investor wählen …</option>
                    @foreach($investors as $inv)<option value="{{ $inv->id }}">{{ $inv->name }}</option>@endforeach
                </select>
                <x-text-input name="name" placeholder="Bezeichnung" class="w-full" required />
                <x-text-input type="number" step="0.01" name="commitment_amount" placeholder="Zusage (EUR)" class="w-full" required />
                <x-text-input type="number" step="0.01" name="interest_rate_percent" placeholder="Zinssatz %" class="w-full" required />
                <x-primary-button>Anlegen</x-primary-button>
            </form>
        </div>
    </div>
</x-app-layout>
