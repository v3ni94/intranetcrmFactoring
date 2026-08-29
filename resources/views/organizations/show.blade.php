<x-app-layout>
    <x-slot name="header">{{ $organization->name }}</x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-lg border border-aurevia-mist p-6">
                <h2 class="text-sm font-semibold text-aurevia-navy mb-3">{{ __('Übersicht') }}</h2>
                <div class="grid grid-cols-2 gap-2 text-sm">
                    <div class="text-aurevia-label-gray">{{ __('Fachrichtung') }}</div><div class="text-right">{{ $organization->specialty }}</div>
                    <div class="text-aurevia-label-gray">{{ __('Adresse') }}</div><div class="text-right">{{ $organization->street }}, {{ $organization->zip }} {{ $organization->city }}</div>
                    <div class="text-aurevia-label-gray">{{ __('Onboarding-Status') }}</div><div class="text-right">{{ $organization->customer_status }}</div>
                    <div class="text-aurevia-label-gray">{{ __('Risikoklasse') }}</div><div class="text-right capitalize">{{ $organization->risk_class ?? '–' }}</div>
                    <div class="text-aurevia-label-gray">{{ __('Rating') }}</div>
                    <div class="text-right">
                        @if($organization->rating)
                            <span class="text-xs font-semibold px-2 py-0.5 rounded bg-aurevia-navy text-white">{{ $organization->rating }}</span>
                            <span class="text-xs text-aurevia-label-gray">({{ $organization->rating_points }} {{ __('P., Aufschlag +') }}{{ number_format(\App\Support\RatingCatalog::feeSurchargePercent($organization->rating), 1, ',', '.') }} {{ __('%-Pkt.') }})</span>
                        @else
                            <span class="text-xs text-aurevia-label-gray">{{ __('ohne Rating') }}</span>
                        @endif
                    </div>
                    <div class="text-aurevia-label-gray">{{ __('Branche') }}</div><div class="text-right">{{ \App\Support\RatingCatalog::SEGMENTS[$organization->segment] ?? '–' }}</div>
                    <div class="text-aurevia-label-gray">{{ __('Kundentyp') }}</div><div class="text-right uppercase">{{ $organization->customer_type }}</div>
                </div>

                {{-- Rating & Einstufung pflegen (v3.00) --}}
                <form method="POST" action="{{ route('organizations.update-rating', $organization) }}"
                      class="grid grid-cols-2 md:grid-cols-4 gap-2 mt-4 pt-4 border-t border-aurevia-mist/60 text-sm items-end">
                    @csrf
                    <div>
                        <label class="block text-[11px] uppercase text-aurevia-label-gray mb-1">{{ __('Punkte (0–100)') }}</label>
                        <input type="number" name="rating_points" min="0" max="100" value="{{ $organization->rating_points }}" required class="w-full rounded-md border-aurevia-mist text-sm" />
                    </div>
                    <div>
                        <label class="block text-[11px] uppercase text-aurevia-label-gray mb-1">{{ __('Branche') }}</label>
                        <select name="segment" class="w-full rounded-md border-aurevia-mist text-sm">
                            <option value="">–</option>
                            @foreach(\App\Support\RatingCatalog::SEGMENTS as $key => $label)
                                <option value="{{ $key }}" @selected($organization->segment === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] uppercase text-aurevia-label-gray mb-1">{{ __('Kundentyp') }}</label>
                        <select name="customer_type" class="w-full rounded-md border-aurevia-mist text-sm">
                            <option value="b2b" @selected($organization->customer_type === 'b2b')>{{ __('B2B (gewerblich)') }}</option>
                            <option value="b2c" @selected($organization->customer_type === 'b2c')>{{ __('B2C (Verbraucher)') }}</option>
                        </select>
                    </div>
                    <button class="px-3 py-2 bg-aurevia-navy text-white rounded-md text-sm hover:bg-aurevia-navy/90">{{ __('Rating speichern') }}</button>
                </form>
                @if($organization->customer_type === 'b2c')
                    <p class="text-[11px] text-amber-700 bg-amber-50 border border-amber-200 rounded p-2 mt-2">
                        {{ __('B2C: Verbraucher als Rechnungsempfänger — Abtretungsinformation und Verbraucherschutzanforderungen beachten (siehe Hilfe & FAQ, rechtlich prüfen lassen).') }}
                    </p>
                @endif
                <div class="flex flex-wrap gap-2 mt-4 pt-4 border-t border-aurevia-mist/60">
                    <form method="POST" action="{{ route('organizations.run-kyc', $organization) }}">
                        @csrf
                        <button class="text-xs text-aurevia-navy border border-aurevia-navy px-2 py-1 rounded-md hover:bg-aurevia-pearl">{{ __('KYC/KYB-Prüfung (Sandbox)') }}</button>
                    </form>
                    <form method="POST" action="{{ route('organizations.run-credit-check', $organization) }}">
                        @csrf
                        <button class="text-xs text-aurevia-navy border border-aurevia-navy px-2 py-1 rounded-md hover:bg-aurevia-pearl">{{ __('Bonitätsauskunft (Sandbox)') }}</button>
                    </form>
                    <form method="POST" action="{{ route('organizations.run-register-check', $organization) }}">
                        @csrf
                        <button class="text-xs text-aurevia-navy border border-aurevia-navy px-2 py-1 rounded-md hover:bg-aurevia-pearl">{{ __('Registerabgleich (Sandbox)') }}</button>
                    </form>
                </div>
            </div>

            <div class="bg-white rounded-lg border border-aurevia-mist p-6">
                <h2 class="text-sm font-semibold text-aurevia-navy mb-3">{{ __('Verträge') }}</h2>
                <table class="w-full text-sm">
                    <thead class="text-[11px] uppercase text-aurevia-label-gray"><tr><th class="text-left pb-2">{{ __('Nummer') }}</th><th class="text-left pb-2">{{ __('Status') }}</th><th class="text-right pb-2">{{ __('Ankaufslinie') }}</th><th class="p-3"></th></tr></thead>
                    <tbody>
                    @forelse($organization->contracts as $c)
                        <tr class="border-t border-aurevia-mist/60">
                            <td class="py-1.5">{{ $c->contract_number }}</td><td class="py-1.5">{{ $c->status }}</td><td class="py-1.5 text-right">{{ eur($c->purchase_line) }}</td>
                            <td class="py-1.5 text-right">
                                <form method="POST" action="{{ route('organizations.sign-contract', $c) }}">
                                    @csrf
                                    <button class="text-xs text-aurevia-navy hover:underline">{{ __('Digital signieren (Demo)') }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-4 text-center text-aurevia-label-gray">{{ __('Kein Vertrag hinterlegt.') }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="bg-white rounded-lg border border-aurevia-mist p-6">
                <h2 class="text-sm font-semibold text-aurevia-navy mb-3">{{ __('Kreditlinien') }}</h2>
                <table class="w-full text-sm">
                    <thead class="text-[11px] uppercase text-aurevia-label-gray"><tr><th class="text-left pb-2">{{ __('Typ') }}</th><th class="text-right pb-2">{{ __('Limit') }}</th><th class="text-right pb-2">{{ __('Genutzt') }}</th></tr></thead>
                    <tbody>
                    @forelse($organization->creditLines as $l)
                        <tr class="border-t border-aurevia-mist/60"><td class="py-1.5">{{ $l->line_type }}</td><td class="py-1.5 text-right">{{ eur($l->limit_amount) }}</td><td class="py-1.5 text-right">{{ eur($l->used_amount) }}</td></tr>
                    @empty
                        <tr><td colspan="3" class="py-4 text-center text-aurevia-label-gray">{{ __('Keine Kreditlinie hinterlegt.') }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-aurevia-mist p-6">
            <h2 class="text-sm font-semibold text-aurevia-navy mb-3">{{ __('Kontakte') }}</h2>
            @forelse($organization->contacts as $c)
                <div class="text-sm border-b border-aurevia-mist/60 py-1.5">{{ $c->fullName() }} · {{ $c->role }}</div>
            @empty
                <p class="text-sm text-aurevia-label-gray">{{ __('Keine Kontakte hinterlegt.') }}</p>
            @endforelse

            <h2 class="text-sm font-semibold text-aurevia-navy mt-6 mb-3">{{ __('Wirtschaftlich Berechtigte') }}</h2>
            @forelse($organization->beneficialOwners as $b)
                <div class="text-sm border-b border-aurevia-mist/60 py-1.5 flex items-center justify-between">
                    <span>{{ $b->first_name }} {{ $b->last_name }} ({{ $b->ownership_percent }}%)</span>
                    <form method="POST" action="{{ route('organizations.run-pep-screening', $b) }}">
                        @csrf
                        <button class="text-xs text-aurevia-navy hover:underline">{{ __('PEP-Screening') }}</button>
                    </form>
                </div>
            @empty
                <p class="text-sm text-aurevia-label-gray">{{ __('Keine wirtschaftlich Berechtigten hinterlegt.') }}</p>
            @endforelse

            <h2 class="text-sm font-semibold text-aurevia-navy mt-6 mb-3">{{ __('KYC/KYB-Fälle') }}</h2>
            @forelse($organization->kycCases as $k)
                <div class="text-sm border-b border-aurevia-mist/60 py-1.5 flex justify-between"><span>{{ $k->case_type }}</span><span>{{ $k->result }}</span></div>
            @empty
                <p class="text-sm text-aurevia-label-gray">{{ __('Keine Fälle hinterlegt.') }}</p>
            @endforelse
        </div>
    </div>
</x-app-layout>
