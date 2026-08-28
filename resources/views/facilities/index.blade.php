<x-app-layout>
    <x-slot name="header">Investoren &amp; Finanzierungsfazilitäten</x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white rounded-lg border border-aurevia-mist overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-aurevia-pearl text-[11px] uppercase text-aurevia-label-gray">
                    <tr>
                        <th class="text-left p-3">Nummer</th><th class="text-left p-3">Investor</th>
                        <th class="text-right p-3">Zusage</th><th class="text-right p-3">Auslastung</th>
                        <th class="text-left p-3">Status</th><th class="text-right p-3"></th>
                    </tr>
                </thead>
                <tbody>
                @forelse($facilities as $f)
                    <tr class="border-t border-aurevia-mist/60">
                        <td class="p-3">{{ $f->facility_number }}</td>
                        <td class="p-3">
                            {{ $f->investorOrganization->name ?? '–' }}
                            @if($f->investorOrganization?->rating)
                                <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded bg-aurevia-navy text-white ml-1">{{ $f->investorOrganization->rating }}</span>
                            @endif
                        </td>
                        <td class="p-3 text-right">{{ eur($f->commitment_amount) }}</td>
                        <td class="p-3 text-right">{{ pct($f->utilization) }}</td>
                        <td class="p-3">
                            <span class="text-xs px-2 py-0.5 rounded {{ match($f->status) { 'aktiv' => 'bg-emerald-100 text-emerald-800', 'gekuendigt' => 'bg-red-100 text-red-800', default => 'bg-aurevia-pearl' } }}">{{ $f->status }}</span>
                            @if($f->early_termination_right && $f->status === 'aktiv')
                                <span class="text-[10px] text-aurevia-label-gray block mt-0.5">Sonderkündigungsrecht{{ $f->termination_notice_days ? ', '.$f->termination_notice_days.' Tage Frist' : '' }}</span>
                            @endif
                            @if($f->terminated_at)
                                <span class="text-[10px] text-aurevia-label-gray block mt-0.5">{{ $f->terminated_at->format('d.m.Y') }} ({{ $f->termination_reason }})</span>
                            @endif
                        </td>
                        <td class="p-3 text-right">
                            @if(in_array($f->status, ['aktiv', 'ausgesetzt']))
                                <form method="POST" action="{{ route('facilities.terminate', $f) }}" class="flex items-center gap-1 justify-end"
                                      onsubmit="return confirm('Fazilität {{ $f->facility_number }} wirklich kündigen?')">
                                    @csrf
                                    <select name="termination_reason" class="text-xs rounded-md border-aurevia-mist py-1">
                                        <option value="ordentlich">ordentlich</option>
                                        <option value="sonderkuendigung">Sonderkündigung</option>
                                        <option value="insolvenz_investor">Insolvenz Investor</option>
                                    </select>
                                    <button class="text-xs text-red-700 underline hover:no-underline">Kündigen</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="p-6 text-center text-aurevia-label-gray">Keine Fazilitäten vorhanden.</td></tr>
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
                <label class="flex items-center gap-2 text-sm text-aurevia-ink">
                    <input type="checkbox" name="early_termination_right" value="1" class="rounded border-aurevia-mist" />
                    Sonderkündigungsrecht vereinbart
                </label>
                <x-text-input type="number" name="termination_notice_days" placeholder="Kündigungsfrist (Tage, optional)" class="w-full" />
                <x-primary-button>Anlegen</x-primary-button>
            </form>
            <p class="text-[11px] text-aurevia-label-gray mt-3">
                Sonderkündigung ist nur möglich, wenn hier vereinbart. Kündigungen (auch wegen Insolvenz des Investors)
                werden mit Grund und Zeitstempel protokolliert.
            </p>
        </div>
    </div>
</x-app-layout>
