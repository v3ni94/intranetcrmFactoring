<x-app-layout>
    <x-slot name="header">{{ __('Personalakte') }} · {{ $user->name }}</x-slot>

    @if(session('status'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm rounded-md p-3 mb-6">{{ session('status') }}</div>
    @endif
    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-800 text-sm rounded-md p-3 mb-6">
            <ul class="list-disc pl-4">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('users.update', $user) }}" x-data="{ role: '{{ old('role', $user->getRoleNames()->first()) }}' }">
        @csrf
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            {{-- Konto & Rolle --}}
            <div class="bg-white rounded-lg border border-aurevia-mist p-4 space-y-3 text-sm">
                <h2 class="text-sm font-semibold text-aurevia-navy">{{ __('Konto & Rolle') }}</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] uppercase text-aurevia-label-gray mb-1">{{ __('Name') }}</label>
                        <input name="name" value="{{ old('name', $user->name) }}" required class="w-full rounded-md border-aurevia-mist text-sm" />
                    </div>
                    <div>
                        <label class="block text-[11px] uppercase text-aurevia-label-gray mb-1">{{ __('E-Mail (geschäftlich / Login)') }}</label>
                        <input name="email" type="email" value="{{ old('email', $user->email) }}" required class="w-full rounded-md border-aurevia-mist text-sm" />
                    </div>
                    <div>
                        <label class="block text-[11px] uppercase text-aurevia-label-gray mb-1">{{ __('Rolle') }}</label>
                        <select name="role" x-model="role" required class="w-full rounded-md border-aurevia-mist text-sm">
                            @foreach($roles as $slug => $label)
                                <option value="{{ $slug }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div x-show="['kunde_admin','kunde_sachbearbeitung'].includes(role)" x-cloak>
                        <label class="block text-[11px] uppercase text-aurevia-label-gray mb-1">{{ __('Kundenorganisation') }}</label>
                        <select name="customer_org_id" :disabled="!['kunde_admin','kunde_sachbearbeitung'].includes(role)" class="w-full rounded-md border-aurevia-mist text-sm">
                            <option value="">{{ __('bitte wählen') }}</option>
                            @foreach($organizations as $org)
                                <option value="{{ $org->id }}" @selected(old('customer_org_id', $user->customer_org_id) == $org->id)>{{ $org->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div x-show="role === 'investor'" x-cloak>
                        <label class="block text-[11px] uppercase text-aurevia-label-gray mb-1">{{ __('Investoren-Organisation') }}</label>
                        <select name="customer_org_id" :disabled="role !== 'investor'" class="w-full rounded-md border-aurevia-mist text-sm">
                            <option value="">{{ __('bitte wählen') }}</option>
                            @foreach($investorOrganizations as $org)
                                <option value="{{ $org->id }}" @selected(old('customer_org_id', $user->customer_org_id) == $org->id)>{{ $org->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] uppercase text-aurevia-label-gray mb-1">{{ __('Eintrittsdatum') }}</label>
                        <input type="date" name="joined_at" value="{{ old('joined_at', $user->joined_at?->toDateString()) }}" class="w-full rounded-md border-aurevia-mist text-sm" />
                    </div>
                    <div>
                        <label class="block text-[11px] uppercase text-aurevia-label-gray mb-1">{{ __('Austrittsdatum') }}</label>
                        <input type="date" name="left_at" value="{{ old('left_at', $user->left_at?->toDateString()) }}" class="w-full rounded-md border-aurevia-mist text-sm" />
                    </div>
                </div>
                <p class="text-[11px] text-aurevia-label-gray">
                    {{ __('Das Konto ist erst ab dem Eintrittsdatum nutzbar und wird nach dem Austrittsdatum automatisch gesperrt — beides greift ohne weiteres Zutun beim Login.') }}
                </p>
            </div>

            {{-- Organisation & Kontakt --}}
            <div class="bg-white rounded-lg border border-aurevia-mist p-4 space-y-3 text-sm">
                <h2 class="text-sm font-semibold text-aurevia-navy">{{ __('Position & Kontakt') }}</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] uppercase text-aurevia-label-gray mb-1">{{ __('Position') }}</label>
                        <input name="position" value="{{ old('position', $user->position) }}" class="w-full rounded-md border-aurevia-mist text-sm" />
                    </div>
                    <div>
                        <label class="block text-[11px] uppercase text-aurevia-label-gray mb-1">{{ __('Abteilung') }}</label>
                        <input name="department" value="{{ old('department', $user->department) }}" class="w-full rounded-md border-aurevia-mist text-sm" />
                    </div>
                    <div>
                        <label class="block text-[11px] uppercase text-aurevia-label-gray mb-1">{{ __('Vorgesetzter (fachlich)') }}</label>
                        <select name="supervisor_id" class="w-full rounded-md border-aurevia-mist text-sm">
                            <option value="">–</option>
                            @foreach($supervisors as $sup)
                                <option value="{{ $sup->id }}" @selected(old('supervisor_id', $user->supervisor_id) == $sup->id)>{{ $sup->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] uppercase text-aurevia-label-gray mb-1">{{ __('Vorgesetzter (disziplinarisch)') }}</label>
                        <select name="disciplinary_supervisor_id" class="w-full rounded-md border-aurevia-mist text-sm">
                            <option value="">–</option>
                            @foreach($supervisors as $sup)
                                <option value="{{ $sup->id }}" @selected(old('disciplinary_supervisor_id', $user->disciplinary_supervisor_id) == $sup->id)>{{ $sup->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] uppercase text-aurevia-label-gray mb-1">{{ __('Telefon (geschäftlich)') }}</label>
                        <input name="phone_business" value="{{ old('phone_business', $user->phone_business) }}" class="w-full rounded-md border-aurevia-mist text-sm" />
                    </div>
                    <div>
                        <label class="block text-[11px] uppercase text-aurevia-label-gray mb-1">{{ __('Telefon (privat)') }}</label>
                        <input name="phone_private" value="{{ old('phone_private', $user->phone_private) }}" class="w-full rounded-md border-aurevia-mist text-sm" />
                    </div>
                    <div class="col-span-2">
                        <label class="block text-[11px] uppercase text-aurevia-label-gray mb-1">{{ __('E-Mail (privat)') }}</label>
                        <input name="email_private" type="email" value="{{ old('email_private', $user->email_private) }}" class="w-full rounded-md border-aurevia-mist text-sm" />
                    </div>
                </div>
            </div>

            {{-- Person & Adresse --}}
            <div class="bg-white rounded-lg border border-aurevia-mist p-4 space-y-3 text-sm">
                <h2 class="text-sm font-semibold text-aurevia-navy">{{ __('Person & Adresse') }}</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="col-span-2">
                        <label class="block text-[11px] uppercase text-aurevia-label-gray mb-1">{{ __('Straße & Hausnummer') }}</label>
                        <input name="street" value="{{ old('street', $user->street) }}" class="w-full rounded-md border-aurevia-mist text-sm" />
                    </div>
                    <div>
                        <label class="block text-[11px] uppercase text-aurevia-label-gray mb-1">{{ __('PLZ') }}</label>
                        <input name="zip" value="{{ old('zip', $user->zip) }}" class="w-full rounded-md border-aurevia-mist text-sm" />
                    </div>
                    <div>
                        <label class="block text-[11px] uppercase text-aurevia-label-gray mb-1">{{ __('Ort') }}</label>
                        <input name="city" value="{{ old('city', $user->city) }}" class="w-full rounded-md border-aurevia-mist text-sm" />
                    </div>
                    <div>
                        <label class="block text-[11px] uppercase text-aurevia-label-gray mb-1">{{ __('Geburtsdatum') }}</label>
                        <input type="date" name="birth_date" value="{{ old('birth_date', $user->birth_date?->toDateString()) }}" class="w-full rounded-md border-aurevia-mist text-sm" />
                    </div>
                    <div>
                        <label class="block text-[11px] uppercase text-aurevia-label-gray mb-1">{{ __('Steuer-ID') }}</label>
                        <input name="tax_id" value="{{ old('tax_id', $user->tax_id) }}" class="w-full rounded-md border-aurevia-mist text-sm" autocomplete="off" />
                    </div>
                </div>
                <p class="text-[11px] text-aurevia-label-gray">{{ __('Steuer-ID und Ausweisnummer werden verschlüsselt gespeichert und erscheinen in keinen Dokumenten oder Exporten.') }}</p>
            </div>

            {{-- Nachweise --}}
            <div class="bg-white rounded-lg border border-aurevia-mist p-4 space-y-3 text-sm">
                <h2 class="text-sm font-semibold text-aurevia-navy">{{ __('Nachweise') }}</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] uppercase text-aurevia-label-gray mb-1">{{ __('Personalausweis-Nr.') }}</label>
                        <input name="id_card_number" value="{{ old('id_card_number', $user->id_card_number) }}" class="w-full rounded-md border-aurevia-mist text-sm" autocomplete="off" />
                    </div>
                    <div>
                        <label class="block text-[11px] uppercase text-aurevia-label-gray mb-1">{{ __('Ausweis gültig bis') }}</label>
                        <input type="date" name="id_card_valid_until" value="{{ old('id_card_valid_until', $user->id_card_valid_until?->toDateString()) }}" class="w-full rounded-md border-aurevia-mist text-sm" />
                    </div>
                    <div>
                        <label class="block text-[11px] uppercase text-aurevia-label-gray mb-1">{{ __('Führungszeugnis vorgelegt am') }}</label>
                        <input type="date" name="criminal_record_check_at" value="{{ old('criminal_record_check_at', $user->criminal_record_check_at?->toDateString()) }}" class="w-full rounded-md border-aurevia-mist text-sm" />
                    </div>
                    <div>
                        <label class="block text-[11px] uppercase text-aurevia-label-gray mb-1">{{ __('SCHUFA-Auskunft vorgelegt am') }}</label>
                        <input type="date" name="schufa_check_at" value="{{ old('schufa_check_at', $user->schufa_check_at?->toDateString()) }}" class="w-full rounded-md border-aurevia-mist text-sm" />
                    </div>
                    <div>
                        <label class="block text-[11px] uppercase text-aurevia-label-gray mb-1">{{ __('Führerschein-Klasse') }}</label>
                        <input name="drivers_license_class" value="{{ old('drivers_license_class', $user->drivers_license_class) }}" class="w-full rounded-md border-aurevia-mist text-sm" />
                    </div>
                    <div>
                        <label class="block text-[11px] uppercase text-aurevia-label-gray mb-1">{{ __('Führerschein gültig bis') }}</label>
                        <input type="date" name="drivers_license_valid_until" value="{{ old('drivers_license_valid_until', $user->drivers_license_valid_until?->toDateString()) }}" class="w-full rounded-md border-aurevia-mist text-sm" />
                    </div>
                    <div class="col-span-2">
                        <label class="block text-[11px] uppercase text-aurevia-label-gray mb-1">{{ __('Notizen (HR)') }}</label>
                        <textarea name="hr_notes" rows="3" class="w-full rounded-md border-aurevia-mist text-sm">{{ old('hr_notes', $user->hr_notes) }}</textarea>
                    </div>
                </div>
                <p class="text-[11px] text-aurevia-label-gray">{{ __('Originaldokumente (Scans) gehören in die geschützte Personalakte im Dokumentenmanagement, nicht in dieses Formular.') }}</p>
            </div>
        </div>

        <div class="flex items-center justify-between mt-6">
            <a href="{{ route('users.index') }}" class="text-sm text-aurevia-navy underline">{{ __('Zurück zur Übersicht') }}</a>
            <button class="px-6 py-2 bg-aurevia-navy text-white rounded-md text-sm hover:bg-aurevia-navy/90">{{ __('Speichern') }}</button>
        </div>
    </form>

    {{-- Gefahrenzone --}}
    <div class="bg-white rounded-lg border border-red-200 p-4 mt-6 flex flex-wrap items-center justify-between gap-3">
        <div class="text-sm">
            <div class="font-semibold text-red-700">{{ __('Benutzer löschen') }}</div>
            <div class="text-xs text-aurevia-label-gray">{{ __('Nur möglich, solange keine Vorgänge, Freigaben oder Tickets mit diesem Konto verknüpft sind — sonst deaktivieren (Audit-Trail bleibt erhalten).') }}</div>
        </div>
        <form method="POST" action="{{ route('users.destroy', $user) }}"
              onsubmit="return confirm('{{ __('Benutzer endgültig löschen?') }}')">
            @csrf
            <button class="px-4 py-2 text-sm text-red-700 border border-red-300 rounded-md hover:bg-red-50">{{ __('Löschen') }}</button>
        </form>
    </div>
</x-app-layout>
