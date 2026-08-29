<x-app-layout>
    <x-slot name="header">{{ __('Cap-Table, Related Parties & Auslagerungen') }}</x-slot>

    <div class="bg-amber-50 border border-amber-200 text-amber-800 text-sm rounded-md p-3 mb-6">
        {{ __('Streng geschütztes Modul. Alle Beteiligungsangaben sind Hypothese/Entwurf ohne Rechtsbindungswirkung und dürfen extern nicht angezeigt werden.') }}
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div class="bg-white rounded-lg border border-aurevia-mist p-4">
            <h2 class="text-sm font-semibold text-aurevia-navy mb-3">{{ __('Cap-Table-Szenarien') }}</h2>
            <table class="w-full text-sm mb-4">
                <thead class="text-[11px] uppercase text-aurevia-label-gray"><tr><th class="text-left pb-2">{{ __('Gesellschafter') }}</th><th class="text-left pb-2">{{ __('Instrument') }}</th><th class="text-left pb-2">{{ __('Szenario') }}</th><th class="text-right pb-2">{{ __('Anteil') }}</th></tr></thead>
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
                        <tr class="border-t border-aurevia-mist/60"><td class="py-1.5">{{ $sh->name }}</td><td colspan="3" class="py-1.5 text-aurevia-label-gray">{{ __('Kein Instrument hinterlegt.') }}</td></tr>
                    @endforelse
                @empty
                    <tr><td colspan="4" class="py-4 text-center text-aurevia-label-gray">{{ __('Keine Gesellschafter erfasst.') }}</td></tr>
                @endforelse
                </tbody>
            </table>

            <div class="grid grid-cols-2 gap-3">
                <form method="POST" action="{{ route('captable.shareholders.store') }}" class="space-y-2 border-t border-aurevia-mist/60 pt-3">
                    @csrf
                    <p class="text-[11px] uppercase text-aurevia-label-gray">{{ __('Neuer Gesellschafter') }}</p>
                    <x-text-input name="name" placeholder="{{ __('Name') }}" class="w-full text-sm" required />
                    <select name="type" class="w-full text-sm rounded-md border-aurevia-mist">
                        <option value="person">{{ __('Person') }}</option>
                        <option value="gesellschaft">{{ __('Gesellschaft') }}</option>
                    </select>
                    <x-primary-button>{{ __('Anlegen') }}</x-primary-button>
                </form>
                <form method="POST" action="{{ route('captable.equity-instruments.store') }}" class="space-y-2 border-t border-aurevia-mist/60 pt-3">
                    @csrf
                    <p class="text-[11px] uppercase text-aurevia-label-gray">{{ __('Neues Instrument') }}</p>
                    <select name="shareholder_id" class="w-full text-sm rounded-md border-aurevia-mist" required>
                        <option value="">{{ __('Gesellschafter …') }}</option>
                        @foreach($shareholders as $sh)<option value="{{ $sh->id }}">{{ $sh->name }}</option>@endforeach
                    </select>
                    <select name="cap_table_scenario_id" class="w-full text-sm rounded-md border-aurevia-mist">
                        <option value="">{{ __('Ohne Szenario') }}</option>
                        @foreach($scenarios as $s)<option value="{{ $s->id }}">{{ $s->label }}</option>@endforeach
                    </select>
                    <select name="instrument_type" class="w-full text-sm rounded-md border-aurevia-mist">
                        <option value="stammkapital">{{ __('Stammkapital') }}</option>
                        <option value="anteile">{{ __('Anteile') }}</option>
                        <option value="wandeldarlehen">{{ __('Wandeldarlehen') }}</option>
                        <option value="virtuelle_beteiligung">{{ __('Virtuelle Beteiligung') }}</option>
                    </select>
                    <x-text-input type="number" step="0.001" name="percentage" placeholder="{{ __('Anteil %') }}" class="w-full text-sm" />
                    <x-primary-button>{{ __('Anlegen') }}</x-primary-button>
                </form>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-aurevia-mist p-4">
            <h2 class="text-sm font-semibold text-aurevia-navy mb-3">{{ __('Related-Party-Register') }}</h2>
            <table class="w-full text-sm mb-4">
                <thead class="text-[11px] uppercase text-aurevia-label-gray"><tr><th class="text-left pb-2">{{ __('Name') }}</th><th class="text-left pb-2">{{ __('Beziehung') }}</th><th class="text-left pb-2">{{ __('Status') }}</th></tr></thead>
                <tbody>
                @forelse($relatedParties as $p)
                    <tr class="border-t border-aurevia-mist/60"><td class="py-1.5">{{ $p->name }}</td><td class="py-1.5">{{ str_replace('_',' ', $p->relation_type) }}</td><td class="py-1.5">{{ str_replace('_',' ', $p->conflict_status) }}</td></tr>
                @empty
                    <tr><td colspan="3" class="py-4 text-center text-aurevia-label-gray">{{ __('Keine Eintragungen.') }}</td></tr>
                @endforelse
                </tbody>
            </table>
            <form method="POST" action="{{ route('captable.related-parties.store') }}" class="space-y-2 border-t border-aurevia-mist/60 pt-3">
                @csrf
                <p class="text-[11px] uppercase text-aurevia-label-gray">{{ __('Neuer Eintrag') }}</p>
                <x-text-input name="name" placeholder="{{ __('Name') }}" class="w-full text-sm" required />
                <select name="relation_type" class="w-full text-sm rounded-md border-aurevia-mist">
                    <option value="organ">{{ __('Organ') }}</option>
                    <option value="gesellschafter">{{ __('Gesellschafter') }}</option>
                    <option value="angehoeriger">{{ __('Angehöriger') }}</option>
                    <option value="sonstige_nahestehende_person">{{ __('Sonstige nahestehende Person') }}</option>
                </select>
                <x-text-input name="description" placeholder="{{ __('Beschreibung') }}" class="w-full text-sm" />
                <x-primary-button>{{ __('Anlegen') }}</x-primary-button>
            </form>
        </div>
    </div>

    <div class="bg-white rounded-lg border border-aurevia-mist p-4">
        <h2 class="text-sm font-semibold text-aurevia-navy mb-3">{{ __('Auslagerungs-/Dienstleisterregister (DORA)') }}</h2>
        <table class="w-full text-sm mb-4">
            <thead class="text-[11px] uppercase text-aurevia-label-gray">
                <tr><th class="text-left pb-2">{{ __('Leistung') }}</th><th class="text-left pb-2">{{ __('Anbieter') }}</th><th class="text-left pb-2">{{ __('Datenzugriff') }}</th><th class="text-left pb-2">{{ __('Kritikalität') }}</th><th class="text-left pb-2">{{ __('DORA-relevant') }}</th></tr>
            </thead>
            <tbody>
            @forelse($outsourcing as $o)
                <tr class="border-t border-aurevia-mist/60">
                    <td class="py-1.5">{{ $o->service }}</td><td class="py-1.5">{{ $o->provider }}</td>
                    <td class="py-1.5">{{ str_replace('_',' ', $o->data_access) }}</td><td class="py-1.5 capitalize">{{ $o->criticality }}</td>
                    <td class="py-1.5">{{ $o->dora_relevant ? __('Ja') : __('Nein') }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="py-4 text-center text-aurevia-label-gray">{{ __('Keine Eintragungen.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
        <form method="POST" action="{{ route('captable.outsourcing.store') }}" class="grid grid-cols-2 md:grid-cols-5 gap-2 border-t border-aurevia-mist/60 pt-3">
            @csrf
            <x-text-input name="service" placeholder="{{ __('Leistung') }}" class="w-full text-sm" required />
            <x-text-input name="provider" placeholder="{{ __('Anbieter') }}" class="w-full text-sm" required />
            <select name="data_access" class="w-full text-sm rounded-md border-aurevia-mist">
                <option value="keine">{{ __('Kein Datenzugriff') }}</option>
                <option value="personenbezogen">{{ __('Personenbezogen') }}</option>
                <option value="finanzdaten">{{ __('Finanzdaten') }}</option>
                <option value="gesundheitsdaten">{{ __('Gesundheitsdaten') }}</option>
            </select>
            <select name="criticality" class="w-full text-sm rounded-md border-aurevia-mist">
                <option value="niedrig">{{ __('Niedrig') }}</option>
                <option value="mittel">{{ __('Mittel') }}</option>
                <option value="hoch">{{ __('Hoch') }}</option>
            </select>
            <x-primary-button>{{ __('Registrieren') }}</x-primary-button>
        </form>
    </div>
</x-app-layout>
