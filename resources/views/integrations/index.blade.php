<x-app-layout>
    <x-slot name="header">{{ __('Integrationen & Schnittstellen') }}</x-slot>

    <div class="bg-amber-50 border border-amber-200 text-amber-800 text-sm rounded-md p-3 mb-6">
        {{ __('Alle Adapter laufen im Sandbox-/Demo-Modus. Kein Anbieter ist fest verdrahtet — jede Kategorie kann bei Produktivsetzung gegen einen echten Provider getauscht werden (Abschnitt 20). Es findet keine echte Datenübertragung an Dritte statt.') }}
    </div>

    <div class="bg-white rounded-lg border border-aurevia-mist overflow-x-auto mb-6">
        <table class="w-full text-sm">
            <thead class="bg-aurevia-pearl text-[11px] uppercase text-aurevia-label-gray">
                <tr>
                    <th class="text-left p-3">{{ __('Kategorie') }}</th><th class="text-left p-3">{{ __('Adapter') }}</th>
                    <th class="text-left p-3">{{ __('Modus') }}</th><th class="text-left p-3">{{ __('Status') }}</th>
                    <th class="text-left p-3">{{ __('Letzter Erfolg') }}</th><th class="text-right p-3">{{ __('Ereignisse') }}</th>
                </tr>
            </thead>
            <tbody>
            @foreach($providers as $p)
                <tr class="border-t border-aurevia-mist/60">
                    <td class="p-3">{{ $p->category }}</td>
                    <td class="p-3">{{ $p->name }}</td>
                    <td class="p-3 uppercase text-[11px] tracking-wide">{{ $p->mode }}</td>
                    <td class="p-3">
                        <span class="text-xs px-2 py-0.5 rounded {{ match($p->status) { 'healthy' => 'bg-emerald-100 text-emerald-800', 'fehler' => 'bg-red-100 text-red-800', default => 'bg-aurevia-pearl' } }}">
                            {{ $p->status }}
                        </span>
                    </td>
                    <td class="p-3">{{ $p->last_success_at ? $p->last_success_at->format('d.m.Y H:i') : __('nie') }}</td>
                    <td class="p-3 text-right">{{ $p->events_count }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div class="bg-white rounded-lg border border-aurevia-mist overflow-x-auto">
        <h2 class="text-sm font-semibold text-aurevia-navy p-3 pb-0">{{ __('Letzte Ereignisse') }}</h2>
        <table class="w-full text-sm">
            <thead class="text-[11px] uppercase text-aurevia-label-gray">
                <tr><th class="text-left p-3">{{ __('Zeit') }}</th><th class="text-left p-3">{{ __('Adapter') }}</th><th class="text-left p-3">{{ __('Status') }}</th><th class="text-left p-3">{{ __('Zusammenfassung') }}</th></tr>
            </thead>
            <tbody>
            @forelse($recentEvents as $e)
                <tr class="border-t border-aurevia-mist/60">
                    <td class="p-3">{{ $e->created_at?->format('d.m.Y H:i') }}</td>
                    <td class="p-3">{{ $e->provider->name ?? '–' }}</td>
                    <td class="p-3">{{ $e->status }}</td>
                    <td class="p-3">{{ $e->summary }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="p-6 text-center text-aurevia-label-gray">{{ __('Noch keine Ereignisse.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</x-app-layout>
