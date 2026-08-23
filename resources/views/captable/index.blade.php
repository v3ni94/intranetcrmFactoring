<x-app-layout>
    <x-slot name="header">Cap-Table, Related Parties &amp; Auslagerungen</x-slot>

    <div class="bg-amber-50 border border-amber-200 text-amber-800 text-sm rounded-md p-3 mb-6">
        Streng geschütztes Modul. Alle Beteiligungsangaben sind Hypothese/Entwurf ohne
        Rechtsbindungswirkung und dürfen extern nicht angezeigt werden.
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div class="bg-white rounded-lg border border-aurevia-mist p-4">
            <h2 class="text-sm font-semibold text-aurevia-navy mb-3">Cap-Table-Szenarien</h2>
            <table class="w-full text-sm mb-4">
                <thead class="text-[11px] uppercase text-aurevia-label-gray"><tr><th class="text-left pb-2">Gesellschafter</th><th class="text-left pb-2">Instrument</th><th class="text-left pb-2">Szenario</th><th class="text-right pb-2">Anteil</th></tr></thead>
                <tbody>
                @forelse($shareholders as $sh)
                    @forelse($sh->equityInstruments as $eq)
                        <tr class="border-t border-aurevia-mist/60">
                            <td class="py-1.5">{{ $sh->name }}</td>
                            <td class="py-1.5">{{ $eq->instrument_type }}</td>
                            <td class="py-1.5">{{ $eq->scenario->label ?? '–' }}</td>
                            <td class="py-1.5 text-right">{{ $eq->percentage ? pct($eq->percentage) : '–' }}</td>
                        </tr>
                    @empty
                        <tr class="border-t border-aurevia-mist/60"><td class="py-1.5">{{ $sh->name }}</td><td colspan="3" class="py-1.5 text-aurevia-label-gray">Kein Instrument hinterlegt.</td></tr>
                    @endforelse
                @empty
                    <tr><td colspan="4" class="py-4 text-center text-aurevia-label-gray">Keine Gesellschafter erfasst.</td></tr>
                @endforelse
                </tbody>
            </table>

            <div class="grid grid-cols-2 gap-3">
                <form method="POST" action="{{ route('captable.shareholders.store') }}" class="space-y-2 border-t border-aurevia-mist/60 pt-3">
                    @csrf
                    <p class="text-[11px] uppercase text-aurevia-label-gray">Neuer Gesellschafter</p>
                    <x-text-input name="name" placeholder="Name" class="w-full text-sm" required />
                    <select name="type" class="w-full text-sm rounded-md border-aurevia-mist">
                        <option value="person">Person</option>
                        <option value="gesellschaft">Gesellschaft</option>
                    </select>
                    <x-primary-button>Anlegen</x-primary-button>
                </form>
                <form method="POST" action="{{ route('captable.equity-instruments.store') }}" class="space-y-2 border-t border-aurevia-mist/60 pt-3">
                    @csrf
                    <p class="text-[11px] uppercase text-aurevia-label-gray">Neues Instrument</p>
                    <select name="shareholder_id" class="w-full text-sm rounded-md border-aurevia-mist" required>
                        <option value="">Gesellschafter …</option>
                        @foreach($shareholders as $sh)<option value="{{ $sh->id }}">{{ $sh->name }}</option>@endforeach
                    </select>
                    <select name="cap_table_scenario_id" class="w-full text-sm rounded-md border-aurevia-mist">
                        <option value="">Ohne Szenario</option>
                        @foreach($scenarios as $s)<option value="{{ $s->id }}">{{ $s->label }}</option>@endforeach
                    </select>
                    <select name="instrument_type" class="w-full text-sm rounded-md border-aurevia-mist">
                        <option value="stammkapital">Stammkapital</option>
                        <option value="anteile">Anteile</option>
                        <option value="wandeldarlehen">Wandeldarlehen</option>
                        <option value="virtuelle_beteiligung">Virtuelle Beteiligung</option>
                    </select>
                    <x-text-input type="number" step="0.001" name="percentage" placeholder="Anteil %" class="w-full text-sm" />
                    <x-primary-button>Anlegen</x-primary-button>
                </form>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-aurevia-mist p-4">
            <h2 class="text-sm font-semibold text-aurevia-navy mb-3">Related-Party-Register</h2>
            <table class="w-full text-sm mb-4">
                <thead class="text-[11px] uppercase text-aurevia-label-gray"><tr><th class="text-left pb-2">Name</th><th class="text-left pb-2">Beziehung</th><th class="text-left pb-2">Status</th></tr></thead>
                <tbody>
                @forelse($relatedParties as $p)
                    <tr class="border-t border-aurevia-mist/60"><td class="py-1.5">{{ $p->name }}</td><td class="py-1.5">{{ str_replace('_',' ', $p->relation_type) }}</td><td class="py-1.5">{{ str_replace('_',' ', $p->conflict_status) }}</td></tr>
                @empty
                    <tr><td colspan="3" class="py-4 text-center text-aurevia-label-gray">Keine Eintragungen.</td></tr>
                @endforelse
                </tbody>
            </table>
            <form method="POST" action="{{ route('captable.related-parties.store') }}" class="space-y-2 border-t border-aurevia-mist/60 pt-3">
                @csrf
                <p class="text-[11px] uppercase text-aurevia-label-gray">Neuer Eintrag</p>
                <x-text-input name="name" placeholder="Name" class="w-full text-sm" required />
                <select name="relation_type" class="w-full text-sm rounded-md border-aurevia-mist">
                    <option value="organ">Organ</option>
                    <option value="gesellschafter">Gesellschafter</option>
                    <option value="angehoeriger">Angehöriger</option>
                    <option value="sonstige_nahestehende_person">Sonstige nahestehende Person</option>
                </select>
                <x-text-input name="description" placeholder="Beschreibung" class="w-full text-sm" />
                <x-primary-button>Anlegen</x-primary-button>
            </form>
        </div>
    </div>

    <div class="bg-white rounded-lg border border-aurevia-mist p-4">
        <h2 class="text-sm font-semibold text-aurevia-navy mb-3">Auslagerungs-/Dienstleisterregister (DORA)</h2>
        <table class="w-full text-sm mb-4">
            <thead class="text-[11px] uppercase text-aurevia-label-gray">
                <tr><th class="text-left pb-2">Leistung</th><th class="text-left pb-2">Anbieter</th><th class="text-left pb-2">Datenzugriff</th><th class="text-left pb-2">Kritikalität</th><th class="text-left pb-2">DORA-relevant</th></tr>
            </thead>
            <tbody>
            @forelse($outsourcing as $o)
                <tr class="border-t border-aurevia-mist/60">
                    <td class="py-1.5">{{ $o->service }}</td><td class="py-1.5">{{ $o->provider }}</td>
                    <td class="py-1.5">{{ str_replace('_',' ', $o->data_access) }}</td><td class="py-1.5 capitalize">{{ $o->criticality }}</td>
                    <td class="py-1.5">{{ $o->dora_relevant ? 'Ja' : 'Nein' }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="py-4 text-center text-aurevia-label-gray">Keine Eintragungen.</td></tr>
            @endforelse
            </tbody>
        </table>
        <form method="POST" action="{{ route('captable.outsourcing.store') }}" class="grid grid-cols-2 md:grid-cols-5 gap-2 border-t border-aurevia-mist/60 pt-3">
            @csrf
            <x-text-input name="service" placeholder="Leistung" class="w-full text-sm" required />
            <x-text-input name="provider" placeholder="Anbieter" class="w-full text-sm" required />
            <select name="data_access" class="w-full text-sm rounded-md border-aurevia-mist">
                <option value="keine">Kein Datenzugriff</option>
                <option value="personenbezogen">Personenbezogen</option>
                <option value="finanzdaten">Finanzdaten</option>
                <option value="gesundheitsdaten">Gesundheitsdaten</option>
            </select>
            <select name="criticality" class="w-full text-sm rounded-md border-aurevia-mist">
                <option value="niedrig">Niedrig</option>
                <option value="mittel">Mittel</option>
                <option value="hoch">Hoch</option>
            </select>
            <x-primary-button>Registrieren</x-primary-button>
        </form>
    </div>
</x-app-layout>
