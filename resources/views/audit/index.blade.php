<x-app-layout>
    <x-slot name="header">Audit &amp; Freigaben</x-slot>

    <div class="bg-white rounded-lg border border-aurevia-mist overflow-x-auto mb-6">
        <table class="w-full text-sm">
            <thead class="bg-aurevia-pearl text-[11px] uppercase text-aurevia-label-gray">
                <tr><th class="text-left p-3">Zeit</th><th class="text-left p-3">Nutzer</th><th class="text-left p-3">Aktion</th><th class="text-left p-3">Objekt</th><th class="text-left p-3">Grund</th></tr>
            </thead>
            <tbody>
            @forelse($events as $e)
                <tr class="border-t border-aurevia-mist/60">
                    <td class="p-3">{{ $e->created_at?->format('d.m.Y H:i') }}</td>
                    <td class="p-3">{{ $e->user->name ?? 'System' }}</td>
                    <td class="p-3">{{ $e->action }}</td>
                    <td class="p-3">{{ class_basename($e->subject_type) }} @if($e->subject_id) #{{ $e->subject_id }} @endif</td>
                    <td class="p-3">{{ $e->reason }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="p-6 text-center text-aurevia-label-gray">Keine Audit-Ereignisse vorhanden.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mb-6">{{ $events->links() }}</div>

    <div class="bg-white rounded-lg border border-aurevia-mist p-4">
        <h2 class="text-sm font-semibold text-aurevia-navy mb-3">Letzte Freigabevorgänge (Vier-Augen-Prinzip)</h2>
        @forelse($approvals as $a)
            <div class="text-sm flex justify-between border-b border-aurevia-mist/60 py-1.5">
                <span>{{ $a->action }} · {{ class_basename($a->subject_type) }} #{{ $a->subject_id }}</span>
                <span>{{ $a->status }} · {{ $a->requester->name ?? '' }} → {{ $a->decider->name ?? 'ausstehend' }}</span>
            </div>
        @empty
            <p class="text-sm text-aurevia-label-gray">Keine offenen Freigabevorgänge.</p>
        @endforelse
    </div>
</x-app-layout>
