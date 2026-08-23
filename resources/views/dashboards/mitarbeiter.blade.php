<x-app-layout>
    <x-slot name="header">Mitarbeiter-Dashboard</x-slot>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <x-kpi-card label="Neue Anträge" :value="$newSubmissions" />
        <x-kpi-card label="Forderungen in Prüfung" :value="$inReview" />
        <x-kpi-card label="Auszahlungen zur Freigabe" :value="$payoutsToApprove" tone="{{ $payoutsToApprove > 0 ? 'warn' : 'good' }}" />
        <x-kpi-card label="Nicht zugeordnete Zahlungen" :value="$unmatchedPayments" tone="{{ $unmatchedPayments > 0 ? 'warn' : 'good' }}" />
        <x-kpi-card label="Überfällige Forderungen" :value="$overdue" tone="{{ $overdue > 0 ? 'bad' : 'good' }}" />
        <x-kpi-card label="Offene Streit-/Mahnfälle" :value="$disputes" />
        <x-kpi-card label="Limitwarnungen" :value="$watchlist" tone="{{ $watchlist > 0 ? 'warn' : 'good' }}" />
    </div>

    <div class="bg-white rounded-lg border border-aurevia-mist p-4">
        <h2 class="text-sm font-semibold text-aurevia-navy mb-3">Meine Aufgaben &amp; Wiedervorlagen</h2>
        <table class="w-full text-sm">
            <thead class="text-[11px] uppercase text-aurevia-label-gray">
                <tr><th class="text-left pb-2">Titel</th><th class="text-left pb-2">Fällig</th><th class="text-left pb-2">Priorität</th><th class="text-left pb-2">Status</th></tr>
            </thead>
            <tbody>
            @forelse($myTasks as $t)
                <tr class="border-t border-aurevia-mist/60">
                    <td class="py-1.5">{{ $t->title }}</td>
                    <td class="py-1.5">{{ dmy($t->due_date) }}</td>
                    <td class="py-1.5 capitalize">{{ $t->priority }}</td>
                    <td class="py-1.5 capitalize">{{ str_replace('_',' ', $t->status) }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="py-4 text-center text-aurevia-label-gray">Keine offenen Aufgaben.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6 flex gap-3 text-sm">
        <a href="{{ route('receivables.index') }}" class="text-white bg-aurevia-navy px-3 py-1.5 rounded-md hover:bg-aurevia-navy/90">Zur Forderungsprüfung</a>
        <a href="{{ route('payments.index') }}" class="text-aurevia-navy border border-aurevia-navy px-3 py-1.5 rounded-md hover:bg-aurevia-pearl">Zahlungszuordnung</a>
    </div>
</x-app-layout>
