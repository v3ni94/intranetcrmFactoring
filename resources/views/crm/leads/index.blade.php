<x-app-layout>
    <x-slot name="header">CRM / Vertrieb – Leads</x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white rounded-lg border border-aurevia-mist overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-aurevia-pearl text-[11px] uppercase text-aurevia-label-gray">
                    <tr>
                        <th class="text-left p-3">Praxis/Firma</th><th class="text-left p-3">Fachrichtung</th>
                        <th class="text-left p-3">Kontakt</th><th class="text-left p-3">Status</th><th class="p-3"></th>
                    </tr>
                </thead>
                <tbody>
                @forelse($leads as $lead)
                    <tr class="border-t border-aurevia-mist/60">
                        <td class="p-3">{{ $lead->company_name }}</td>
                        <td class="p-3">{{ $lead->specialty }}</td>
                        <td class="p-3">{{ $lead->contact_name }}</td>
                        <td class="p-3">{{ $lead->status }}</td>
                        <td class="p-3 text-right">
                            <form method="POST" action="{{ route('crm.leads.update-status', $lead) }}" class="inline-flex gap-1">
                                @csrf
                                <select name="status" onchange="this.form.submit()" class="text-xs rounded-md border-aurevia-mist">
                                    @foreach($statuses as $s)
                                        <option value="{{ $s }}" {{ $lead->status === $s ? 'selected' : '' }}>{{ $s }}</option>
                                    @endforeach
                                </select>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="p-6 text-center text-aurevia-label-gray">Keine Leads vorhanden.</td></tr>
                @endforelse
                </tbody>
            </table>
            <div class="p-3">{{ $leads->links() }}</div>
        </div>

        <div class="bg-white rounded-lg border border-aurevia-mist p-4">
            <h2 class="text-sm font-semibold text-aurevia-navy mb-3">Neuer Lead</h2>
            <form method="POST" action="{{ route('crm.leads.store') }}" class="space-y-3">
                @csrf
                <x-text-input name="company_name" placeholder="Praxis/Firma" class="w-full" required />
                <x-text-input name="specialty" placeholder="Fachrichtung" class="w-full" />
                <x-text-input name="contact_name" placeholder="Ansprechpartner" class="w-full" />
                <x-text-input name="contact_email" type="email" placeholder="E-Mail" class="w-full" />
                <x-text-input name="source" placeholder="Quelle / Empfehlungsgeber" class="w-full" />
                <x-primary-button>Lead anlegen</x-primary-button>
            </form>
        </div>
    </div>

    <div class="mt-6">
        <a href="{{ route('crm.opportunities.index') }}" class="text-sm text-aurevia-navy hover:underline">Zur Opportunity-Pipeline →</a>
    </div>
</x-app-layout>
