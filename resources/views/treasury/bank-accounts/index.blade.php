<x-app-layout>
    <x-slot name="header">{{ __('Treasury & Bankkonten') }}</x-slot>

    <div class="bg-white rounded-lg border border-aurevia-mist overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-aurevia-pearl text-[11px] uppercase text-aurevia-label-gray">
                <tr>
                    <th class="text-left p-3">{{ __('Konto') }}</th><th class="text-left p-3">{{ __('Bank') }}</th><th class="text-left p-3">{{ __('Zweck') }}</th>
                    <th class="text-right p-3">{{ __('Saldo') }}</th><th class="text-right p-3">{{ __('Bewegungen') }}</th>
                </tr>
            </thead>
            <tbody>
            @forelse($accounts as $a)
                <tr class="border-t border-aurevia-mist/60">
                    <td class="p-3">{{ $a->account_name }}</td>
                    <td class="p-3">{{ $a->bank_name }}</td>
                    <td class="p-3 capitalize">{{ $a->purpose }}</td>
                    <td class="p-3 text-right font-medium">{{ eur($a->balance_amount) }}</td>
                    <td class="p-3 text-right">{{ $a->transactions_count }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="p-6 text-center text-aurevia-label-gray">{{ __('Keine Bankkonten hinterlegt.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <p class="text-[11px] text-aurevia-label-gray mt-4">
        {{ __('Alle Konten sind Demo-Konten bei einer fiktiven Bank („Medizinbank AG – Demo“). Es finden keine echten Zahlungen statt.') }}
        {{ __('Zahlungseingänge und -zuordnung siehe „Zahlungseingänge“.') }}
    </p>
</x-app-layout>
