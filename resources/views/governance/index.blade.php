<x-app-layout>
    <x-slot name="header">Projekt, Annahmen &amp; Beschlüsse</x-slot>

    <div class="bg-amber-50 border border-amber-200 text-amber-800 text-sm rounded-md p-3 mb-6">
        Geschützter interner Bereich. Inhalte sind Entwurfs- oder Hypothesenstatus und dürfen extern nicht angezeigt werden.
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div class="bg-white rounded-lg border border-aurevia-mist p-4">
            <h2 class="text-sm font-semibold text-aurevia-navy mb-3">Decision Log</h2>
            <table class="w-full text-sm">
                <thead class="text-[11px] uppercase text-aurevia-label-gray"><tr><th class="text-left pb-2">ID</th><th class="text-left pb-2">Titel</th><th class="text-left pb-2">Status</th></tr></thead>
                <tbody>
                @forelse($decisions as $d)
                    <tr class="border-t border-aurevia-mist/60"><td class="py-1.5">{{ $d->decision_id }}</td><td class="py-1.5">{{ $d->title }}</td><td class="py-1.5">{{ $d->status }}</td></tr>
                @empty
                    <tr><td colspan="3" class="py-4 text-center text-aurevia-label-gray">Noch keine Beschlüsse erfasst.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="bg-white rounded-lg border border-aurevia-mist p-4">
            <h2 class="text-sm font-semibold text-aurevia-navy mb-3">Vorschlagsdatensätze Gründungsteam (keine produktiven Rechte)</h2>
            <table class="w-full text-sm">
                <thead class="text-[11px] uppercase text-aurevia-label-gray"><tr><th class="text-left pb-2">Name</th><th class="text-left pb-2">Fokus</th><th class="text-left pb-2">Status</th></tr></thead>
                <tbody>
                @foreach($persons as $p)
                    <tr class="border-t border-aurevia-mist/60"><td class="py-1.5">{{ $p['name'] }}</td><td class="py-1.5">{{ $p['focus'] }}</td><td class="py-1.5">{{ $p['role_status'] }}</td></tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white rounded-lg border border-aurevia-mist p-4">
        <h2 class="text-sm font-semibold text-aurevia-navy mb-3">Finanzszenarien (Hypothese)</h2>
        <table class="w-full text-sm">
            <thead class="text-[11px] uppercase text-aurevia-label-gray"><tr><th class="text-left pb-2">Szenario</th><th class="text-right pb-2">Portfolio Jahr 1</th><th class="text-right pb-2">Status</th></tr></thead>
            <tbody>
            @foreach($scenarios as $s)
                <tr class="border-t border-aurevia-mist/60"><td class="py-1.5">{{ $s->label }}</td><td class="py-1.5 text-right">{{ eur($s->portfolio_year1_eur) }}</td><td class="py-1.5 text-right">{{ $s->status }}</td></tr>
            @endforeach
            </tbody>
        </table>
    </div>
</x-app-layout>
