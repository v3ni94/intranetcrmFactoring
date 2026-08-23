<x-app-layout>
    <x-slot name="header">{{ $organization->name }}</x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-lg border border-aurevia-mist p-6">
                <h2 class="text-sm font-semibold text-aurevia-navy mb-3">Übersicht</h2>
                <div class="grid grid-cols-2 gap-2 text-sm">
                    <div class="text-aurevia-label-gray">Fachrichtung</div><div class="text-right">{{ $organization->specialty }}</div>
                    <div class="text-aurevia-label-gray">Adresse</div><div class="text-right">{{ $organization->street }}, {{ $organization->zip }} {{ $organization->city }}</div>
                    <div class="text-aurevia-label-gray">Onboarding-Status</div><div class="text-right">{{ $organization->customer_status }}</div>
                    <div class="text-aurevia-label-gray">Risikoklasse</div><div class="text-right capitalize">{{ $organization->risk_class ?? '–' }}</div>
                </div>
            </div>

            <div class="bg-white rounded-lg border border-aurevia-mist p-6">
                <h2 class="text-sm font-semibold text-aurevia-navy mb-3">Verträge</h2>
                <table class="w-full text-sm">
                    <thead class="text-[11px] uppercase text-aurevia-label-gray"><tr><th class="text-left pb-2">Nummer</th><th class="text-left pb-2">Status</th><th class="text-right pb-2">Ankaufslinie</th></tr></thead>
                    <tbody>
                    @forelse($organization->contracts as $c)
                        <tr class="border-t border-aurevia-mist/60"><td class="py-1.5">{{ $c->contract_number }}</td><td class="py-1.5">{{ $c->status }}</td><td class="py-1.5 text-right">{{ eur($c->purchase_line) }}</td></tr>
                    @empty
                        <tr><td colspan="3" class="py-4 text-center text-aurevia-label-gray">Kein Vertrag hinterlegt.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="bg-white rounded-lg border border-aurevia-mist p-6">
                <h2 class="text-sm font-semibold text-aurevia-navy mb-3">Kreditlinien</h2>
                <table class="w-full text-sm">
                    <thead class="text-[11px] uppercase text-aurevia-label-gray"><tr><th class="text-left pb-2">Typ</th><th class="text-right pb-2">Limit</th><th class="text-right pb-2">Genutzt</th></tr></thead>
                    <tbody>
                    @forelse($organization->creditLines as $l)
                        <tr class="border-t border-aurevia-mist/60"><td class="py-1.5">{{ $l->line_type }}</td><td class="py-1.5 text-right">{{ eur($l->limit_amount) }}</td><td class="py-1.5 text-right">{{ eur($l->used_amount) }}</td></tr>
                    @empty
                        <tr><td colspan="3" class="py-4 text-center text-aurevia-label-gray">Keine Kreditlinie hinterlegt.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-aurevia-mist p-6">
            <h2 class="text-sm font-semibold text-aurevia-navy mb-3">Kontakte</h2>
            @forelse($organization->contacts as $c)
                <div class="text-sm border-b border-aurevia-mist/60 py-1.5">{{ $c->fullName() }} · {{ $c->role }}</div>
            @empty
                <p class="text-sm text-aurevia-label-gray">Keine Kontakte hinterlegt.</p>
            @endforelse

            <h2 class="text-sm font-semibold text-aurevia-navy mt-6 mb-3">KYC/KYB-Fälle</h2>
            @forelse($organization->kycCases as $k)
                <div class="text-sm border-b border-aurevia-mist/60 py-1.5 flex justify-between"><span>{{ $k->case_type }}</span><span>{{ $k->result }}</span></div>
            @empty
                <p class="text-sm text-aurevia-label-gray">Keine Fälle hinterlegt.</p>
            @endforelse
        </div>
    </div>
</x-app-layout>
