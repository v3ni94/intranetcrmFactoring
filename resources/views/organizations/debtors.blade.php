<x-app-layout>
    <x-slot name="header">{{ __('Debitoren / Rechnungsempfänger') }}</x-slot>

    <div class="bg-white rounded-lg border border-aurevia-mist overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-aurevia-pearl text-[11px] uppercase text-aurevia-label-gray">
                <tr><th class="text-left p-3">{{ __('Name / Pseudonym') }}</th><th class="text-left p-3">{{ __('Ort') }}</th><th class="text-left p-3">{{ __('Risikoklasse') }}</th></tr>
            </thead>
            <tbody>
            @forelse($debtors as $d)
                <tr class="border-t border-aurevia-mist/60">
                    <td class="p-3">{{ $d->pseudonym_id ?? $d->name }}</td>
                    <td class="p-3">{{ $d->city }}</td>
                    <td class="p-3 capitalize">{{ $d->risk_class ?? '–' }}</td>
                </tr>
            @empty
                <tr><td colspan="3" class="p-6 text-center text-aurevia-label-gray">{{ __('Keine Debitoren vorhanden.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $debtors->links() }}</div>
    <p class="text-[11px] text-aurevia-label-gray mt-4">{{ __('Private Rechnungsempfänger werden ausschließlich pseudonymisiert geführt (Medical Data Firewall).') }}</p>
</x-app-layout>
