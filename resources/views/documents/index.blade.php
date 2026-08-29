<x-app-layout>
    <x-slot name="header">{{ __('Verträge & Dokumente') }}</x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white rounded-lg border border-aurevia-mist overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-aurevia-pearl text-[11px] uppercase text-aurevia-label-gray">
                    <tr><th class="text-left p-3">{{ __('Titel') }}</th><th class="text-left p-3">{{ __('Kategorie') }}</th><th class="text-left p-3">{{ __('Sichtbarkeit') }}</th><th class="p-3"></th></tr>
                </thead>
                <tbody>
                @forelse($documents as $d)
                    <tr class="border-t border-aurevia-mist/60">
                        <td class="p-3">{{ $d->title }}</td>
                        <td class="p-3">{{ $d->category }}</td>
                        <td class="p-3">
                            <span class="text-xs px-2 py-0.5 rounded {{ $d->visibility === 'extern_freigegeben' ? 'bg-emerald-100 text-emerald-800' : 'bg-aurevia-pearl' }}">{{ str_replace('_',' ', $d->visibility) }}</span>
                            @if($d->export_locked)<span class="text-xs text-red-600 ml-1">{{ __('Sperrvermerk') }}</span>@endif
                        </td>
                        <td class="p-3 text-right">
                            @if($d->storage_path)
                                <a href="{{ route('documents.download', $d) }}" class="text-aurevia-navy hover:underline">{{ __('Herunterladen') }}</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="p-6 text-center text-aurevia-label-gray">{{ __('Keine Dokumente vorhanden.') }}</td></tr>
                @endforelse
                </tbody>
            </table>
            <div class="p-3">{{ $documents->links() }}</div>
        </div>

        @can('upload-documents')
        <div class="bg-white rounded-lg border border-aurevia-mist p-4">
            <h2 class="text-sm font-semibold text-aurevia-navy mb-3">{{ __('Dokument ablegen') }}</h2>
            <form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data" class="space-y-3">
                @csrf
                <x-text-input name="title" placeholder="{{ __('Titel') }}" class="w-full" required />
                <select name="category" class="w-full text-sm rounded-md border-aurevia-mist" required>
                    <option value="vertrag">{{ __('Vertrag') }}</option>
                    <option value="onboarding">{{ __('Onboarding') }}</option>
                    <option value="kyc">{{ __('KYC') }}</option>
                    <option value="rechnung">{{ __('Rechnung') }}</option>
                    <option value="board_pack">{{ __('Board Pack') }}</option>
                    <option value="sonstiges">{{ __('Sonstiges') }}</option>
                </select>
                <select name="visibility" class="w-full text-sm rounded-md border-aurevia-mist" required>
                    <option value="intern">{{ __('Intern') }}</option>
                    <option value="vertraulich">{{ __('Vertraulich') }}</option>
                    <option value="externe_freigabe_ausstehend">{{ __('Externe Freigabe ausstehend') }}</option>
                    <option value="extern_freigegeben">{{ __('Extern freigegeben') }}</option>
                    <option value="gesperrt">{{ __('Gesperrt') }}</option>
                </select>
                <input type="file" name="file" class="w-full text-sm" />
                <x-primary-button>{{ __('Ablegen') }}</x-primary-button>
            </form>
        </div>
        @else
        <div class="bg-white rounded-lg border border-aurevia-mist p-4 text-sm text-aurevia-label-gray">
            {{ __('Nur zum Lesen. Neue Dokumente werden ausschließlich von Aurevia-internen Rollen abgelegt.') }}
        </div>
        @endcan
    </div>
</x-app-layout>
