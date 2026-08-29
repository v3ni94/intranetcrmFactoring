<x-app-layout>
    <x-slot name="header">{{ __('Benutzerverwaltung') }}</x-slot>

    @if(session('created_user'))
        <div class="bg-emerald-50 border border-emerald-300 text-emerald-900 text-sm rounded-md p-4 mb-6">
            <div class="font-semibold mb-1">{{ __('Zugangsdaten (werden nur einmal angezeigt):') }}</div>
            <div class="font-mono">{{ session('created_user')['email'] }}</div>
            <div class="font-mono text-lg">{{ session('created_user')['password'] }}</div>
            <div class="mt-2 text-xs text-emerald-800">
                {{ __('Bitte sicher übermitteln (z. B. telefonisch oder per Passwortmanager-Freigabe, nicht unverschlüsselt per E-Mail). Interne Rollen, Investoren und Beiräte werden beim ersten Login automatisch zur Einrichtung der Zwei-Faktor-Authentifizierung geführt.') }}
            </div>
        </div>
    @elseif(session('status'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm rounded-md p-3 mb-6">{{ session('status') }}</div>
    @endif

    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-800 text-sm rounded-md p-3 mb-6">
            <ul class="list-disc pl-4">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    {{-- Neuen Benutzer anlegen --}}
    <div class="bg-white rounded-lg border border-aurevia-mist p-4 mb-6" x-data="{ role: '{{ old('role', '') }}' }">
        <h2 class="text-sm font-semibold text-aurevia-navy mb-3">{{ __('Neuen Benutzer anlegen') }}</h2>
        <form method="POST" action="{{ route('users.store') }}" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-3 text-sm">
            @csrf
            <div>
                <label class="block text-[11px] uppercase text-aurevia-label-gray mb-1">{{ __('Name') }}</label>
                <input name="name" value="{{ old('name') }}" required class="w-full rounded-md border-aurevia-mist text-sm" />
            </div>
            <div>
                <label class="block text-[11px] uppercase text-aurevia-label-gray mb-1">{{ __('E-Mail') }}</label>
                <input name="email" type="email" value="{{ old('email') }}" required class="w-full rounded-md border-aurevia-mist text-sm" />
            </div>
            <div>
                <label class="block text-[11px] uppercase text-aurevia-label-gray mb-1">{{ __('Rolle') }}</label>
                <select name="role" x-model="role" required class="w-full rounded-md border-aurevia-mist text-sm">
                    <option value="">{{ __('bitte wählen') }}</option>
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
                        <option value="{{ $org->id }}" @selected(old('customer_org_id') == $org->id)>{{ $org->name }}</option>
                    @endforeach
                </select>
            </div>
            <div x-show="role === 'investor'" x-cloak>
                <label class="block text-[11px] uppercase text-aurevia-label-gray mb-1">{{ __('Investoren-Organisation') }}</label>
                <select name="customer_org_id" :disabled="role !== 'investor'" class="w-full rounded-md border-aurevia-mist text-sm">
                    <option value="">{{ __('bitte wählen') }}</option>
                    @foreach($investorOrganizations as $org)
                        <option value="{{ $org->id }}" @selected(old('customer_org_id') == $org->id)>{{ $org->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end">
                <button class="w-full md:w-auto px-4 py-2 bg-aurevia-navy text-white rounded-md text-sm hover:bg-aurevia-navy/90">
                    {{ __('Anlegen') }}
                </button>
            </div>
        </form>
        <p class="text-xs text-aurevia-label-gray mt-3">
            {{ __('Das Startpasswort wird automatisch erzeugt und nach dem Anlegen einmalig angezeigt. Kunden-Nutzer sehen ausschließlich Forderungen und Dokumente ihrer eigenen Organisation.') }}
        </p>
    </div>

    {{-- Benutzerliste --}}
    <div class="bg-white rounded-lg border border-aurevia-mist overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-aurevia-pearl text-[11px] uppercase text-aurevia-label-gray">
                <tr>
                    <th class="text-left p-3">{{ __('Name') }}</th>
                    <th class="text-left p-3">{{ __('E-Mail') }}</th>
                    <th class="text-left p-3">{{ __('Rolle') }}</th>
                    <th class="text-left p-3">{{ __('Organisation') }}</th>
                    <th class="text-left p-3">{{ __('MFA') }}</th>
                    <th class="text-left p-3">{{ __('Status') }}</th>
                    <th class="text-right p-3">{{ __('Aktionen') }}</th>
                </tr>
            </thead>
            <tbody>
            @foreach($users as $user)
                <tr class="border-t border-aurevia-mist/60 {{ $user->effectiveStatus() === 'aktiv' ? '' : 'opacity-50' }}">
                    <td class="p-3">{{ $user->name }}</td>
                    <td class="p-3">{{ $user->email }}</td>
                    <td class="p-3">{{ $user->primaryRoleLabel() }}</td>
                    <td class="p-3">{{ $user->organization->name ?? '–' }}</td>
                    <td class="p-3">
                        @if($user->hasConfirmedTwoFactor())
                            <span class="text-xs px-2 py-0.5 rounded bg-emerald-100 text-emerald-800">{{ __('aktiv') }}</span>
                        @else
                            <span class="text-xs px-2 py-0.5 rounded bg-aurevia-pearl">{{ __('ausstehend') }}</span>
                        @endif
                    </td>
                    <td class="p-3">
                        @php $status = $user->effectiveStatus(); @endphp
                        <span class="text-xs px-2 py-0.5 rounded whitespace-nowrap {{ [
                            'aktiv' => 'bg-emerald-100 text-emerald-800',
                            'deaktiviert' => 'bg-red-100 text-red-800',
                            'wartet_auf_eintritt' => 'bg-amber-100 text-amber-800',
                            'ausgetreten' => 'bg-aurevia-pearl text-aurevia-label-gray',
                        ][$status] }}">
                            {{ [
                                'aktiv' => __('aktiv'),
                                'deaktiviert' => __('deaktiviert'),
                                'wartet_auf_eintritt' => __('wartet auf Eintritt'),
                                'ausgetreten' => __('ausgetreten'),
                            ][$status] }}
                        </span>
                    </td>
                    <td class="p-3 text-right whitespace-nowrap">
                        <a href="{{ route('users.edit', $user) }}" class="text-xs text-aurevia-navy underline hover:no-underline">{{ __('Bearbeiten') }}</a>
                        @if($user->id !== auth()->id())
                            <form method="POST" action="{{ route('users.toggle-active', $user) }}" class="inline ml-2">
                                @csrf
                                <button class="text-xs text-aurevia-navy underline hover:no-underline"
                                    onclick="return confirm('{{ $user->is_active ? __('Konto wirklich deaktivieren?') : __('Konto reaktivieren?') }}')">
                                    {{ $user->is_active ? __('Deaktivieren') : __('Reaktivieren') }}
                                </button>
                            </form>
                            <form method="POST" action="{{ route('users.reset-password', $user) }}" class="inline ml-2">
                                @csrf
                                <button class="text-xs text-aurevia-navy underline hover:no-underline"
                                    onclick="return confirm('{{ __('Neues Startpasswort erzeugen? Das bisherige wird ungültig.') }}')">
                                    {{ __('Passwort zurücksetzen') }}
                                </button>
                            </form>
                        @else
                            <span class="text-xs text-aurevia-label-gray">{{ __('eigenes Konto') }}</span>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div class="p-3">{{ $users->links() }}</div>
    </div>
</x-app-layout>
