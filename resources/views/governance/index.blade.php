<x-app-layout>
    <x-slot name="header">{{ __('Projekt, Annahmen & Beschlüsse') }}</x-slot>

    <div class="bg-amber-50 border border-amber-200 text-amber-800 text-sm rounded-md p-3 mb-6">
        {{ __('Geschützter interner Bereich. Inhalte sind Entwurfs- oder Hypothesenstatus und dürfen extern nicht angezeigt werden.') }}
    </div>

    <div class="bg-white rounded-lg border border-aurevia-mist p-4 mb-6">
        <h2 class="text-sm font-semibold text-aurevia-navy mb-3">{{ __('Workstreams A–J') }}</h2>
        <table class="w-full text-sm">
            <thead class="text-[11px] uppercase text-aurevia-label-gray">
                <tr>
                    <th class="text-left pb-2">#</th><th class="text-left pb-2">{{ __('Workstream') }}</th>
                    <th class="text-left pb-2">{{ __('Owner') }}</th><th class="text-left pb-2">{{ __('Stellvertretung') }}</th>
                    <th class="text-left pb-2">{{ __('Termin') }}</th><th class="text-left pb-2">{{ __('Status') }}</th>
                </tr>
            </thead>
            <tbody>
            @forelse($workstreams as $w)
                <tr class="border-t border-aurevia-mist/60">
                    <td class="py-1.5 font-medium text-aurevia-navy">{{ $w->code }}</td>
                    <td class="py-1.5">{{ $w->title }}</td>
                    <td class="py-1.5">{{ $w->owner->name ?? '–' }}</td>
                    <td class="py-1.5">{{ $w->deputy->name ?? '–' }}</td>
                    <td class="py-1.5">{{ dmy($w->due_date) }}</td>
                    <td class="py-1.5">
                        <span class="text-xs px-2 py-0.5 rounded {{ $w->status === 'abgeschlossen' ? 'bg-emerald-100 text-emerald-800' : ($w->status === 'blockiert' ? 'bg-red-100 text-red-800' : 'bg-aurevia-pearl') }}">
                            {{ str_replace('_', ' ', $w->status) }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="py-4 text-center text-aurevia-label-gray">{{ __('Noch keine Workstreams erfasst.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="bg-white rounded-lg border border-aurevia-mist p-4 mb-6">
        <h2 class="text-sm font-semibold text-aurevia-navy mb-3">Risk Log</h2>
        <table class="w-full text-sm">
            <thead class="text-[11px] uppercase text-aurevia-label-gray">
                <tr>
                    <th class="text-left pb-2">{{ __('Risiko') }}</th><th class="text-left pb-2">{{ __('Workstream') }}</th>
                    <th class="text-left pb-2">{{ __("Eintrittsw'keit") }}</th><th class="text-left pb-2">{{ __('Auswirkung') }}</th>
                    <th class="text-left pb-2">{{ __('Maßnahme') }}</th><th class="text-left pb-2">{{ __('Owner') }}</th><th class="text-left pb-2">{{ __('Status') }}</th>
                </tr>
            </thead>
            <tbody>
            @forelse($risks as $r)
                <tr class="border-t border-aurevia-mist/60">
                    <td class="py-1.5">{{ $r->title }}</td>
                    <td class="py-1.5">{{ $r->workstream->code ?? '–' }}</td>
                    <td class="py-1.5 capitalize">{{ $r->probability }}</td>
                    <td class="py-1.5 capitalize">{{ $r->impact }}</td>
                    <td class="py-1.5">{{ $r->mitigation }}</td>
                    <td class="py-1.5">{{ $r->owner->name ?? '–' }}</td>
                    <td class="py-1.5 capitalize">{{ str_replace('_', ' ', $r->status) }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="py-4 text-center text-aurevia-label-gray">{{ __('Noch keine Risiken erfasst.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div class="bg-white rounded-lg border border-aurevia-mist p-4">
            <h2 class="text-sm font-semibold text-aurevia-navy mb-3">Decision Log</h2>
            <table class="w-full text-sm">
                <thead class="text-[11px] uppercase text-aurevia-label-gray"><tr><th class="text-left pb-2">ID</th><th class="text-left pb-2">{{ __('Titel') }}</th><th class="text-left pb-2">{{ __('Status') }}</th></tr></thead>
                <tbody>
                @forelse($decisions as $d)
                    <tr class="border-t border-aurevia-mist/60"><td class="py-1.5">{{ $d->decision_id }}</td><td class="py-1.5">{{ $d->title }}</td><td class="py-1.5">{{ $d->status }}</td></tr>
                @empty
                    <tr><td colspan="3" class="py-4 text-center text-aurevia-label-gray">{{ __('Noch keine Beschlüsse erfasst.') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="bg-white rounded-lg border border-aurevia-mist p-4">
            <h2 class="text-sm font-semibold text-aurevia-navy mb-3">{{ __('Vorschlagsdatensätze Gründungsteam (keine produktiven Rechte)') }}</h2>
            <table class="w-full text-sm">
                <thead class="text-[11px] uppercase text-aurevia-label-gray"><tr><th class="text-left pb-2">{{ __('Name') }}</th><th class="text-left pb-2">{{ __('Fokus') }}</th><th class="text-left pb-2">{{ __('Status') }}</th></tr></thead>
                <tbody>
                @foreach($persons as $p)
                    <tr class="border-t border-aurevia-mist/60"><td class="py-1.5">{{ $p['name'] }}</td><td class="py-1.5">{{ $p['focus'] }}</td><td class="py-1.5">{{ $p['role_status'] }}</td></tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white rounded-lg border border-aurevia-mist p-4">
        <h2 class="text-sm font-semibold text-aurevia-navy mb-3">{{ __('Finanzszenarien (Hypothese)') }}</h2>
        <table class="w-full text-sm">
            <thead class="text-[11px] uppercase text-aurevia-label-gray"><tr><th class="text-left pb-2">{{ __('Szenario') }}</th><th class="text-right pb-2">{{ __('Portfolio Jahr 1') }}</th><th class="text-right pb-2">{{ __('Status') }}</th></tr></thead>
            <tbody>
            @foreach($scenarios as $s)
                <tr class="border-t border-aurevia-mist/60"><td class="py-1.5">{{ $s->label }}</td><td class="py-1.5 text-right">{{ eur($s->portfolio_year1_eur) }}</td><td class="py-1.5 text-right">{{ $s->status }}</td></tr>
            @endforeach
            </tbody>
        </table>
    </div>
</x-app-layout>
