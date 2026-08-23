<x-app-layout>
    <x-slot name="header">Verträge &amp; Dokumente</x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white rounded-lg border border-aurevia-mist overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-aurevia-pearl text-[11px] uppercase text-aurevia-label-gray">
                    <tr><th class="text-left p-3">Titel</th><th class="text-left p-3">Kategorie</th><th class="text-left p-3">Sichtbarkeit</th><th class="p-3"></th></tr>
                </thead>
                <tbody>
                @forelse($documents as $d)
                    <tr class="border-t border-aurevia-mist/60">
                        <td class="p-3">{{ $d->title }}</td>
                        <td class="p-3">{{ $d->category }}</td>
                        <td class="p-3">
                            <span class="text-xs px-2 py-0.5 rounded {{ $d->visibility === 'extern_freigegeben' ? 'bg-emerald-100 text-emerald-800' : 'bg-aurevia-pearl' }}">{{ str_replace('_',' ', $d->visibility) }}</span>
                            @if($d->export_locked)<span class="text-xs text-red-600 ml-1">Sperrvermerk</span>@endif
                        </td>
                        <td class="p-3 text-right">
                            @if($d->storage_path)
                                <a href="{{ route('documents.download', $d) }}" class="text-aurevia-navy hover:underline">Herunterladen</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="p-6 text-center text-aurevia-label-gray">Keine Dokumente vorhanden.</td></tr>
                @endforelse
                </tbody>
            </table>
            <div class="p-3">{{ $documents->links() }}</div>
        </div>

        <div class="bg-white rounded-lg border border-aurevia-mist p-4">
            <h2 class="text-sm font-semibold text-aurevia-navy mb-3">Dokument ablegen</h2>
            <form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data" class="space-y-3">
                @csrf
                <x-text-input name="title" placeholder="Titel" class="w-full" required />
                <select name="category" class="w-full text-sm rounded-md border-aurevia-mist" required>
                    <option value="vertrag">Vertrag</option>
                    <option value="onboarding">Onboarding</option>
                    <option value="kyc">KYC</option>
                    <option value="rechnung">Rechnung</option>
                    <option value="board_pack">Board Pack</option>
                    <option value="sonstiges">Sonstiges</option>
                </select>
                <select name="visibility" class="w-full text-sm rounded-md border-aurevia-mist" required>
                    <option value="intern">Intern</option>
                    <option value="vertraulich">Vertraulich</option>
                    <option value="externe_freigabe_ausstehend">Externe Freigabe ausstehend</option>
                    <option value="extern_freigegeben">Extern freigegeben</option>
                    <option value="gesperrt">Gesperrt</option>
                </select>
                <input type="file" name="file" class="w-full text-sm" />
                <x-primary-button>Ablegen</x-primary-button>
            </form>
        </div>
    </div>
</x-app-layout>
