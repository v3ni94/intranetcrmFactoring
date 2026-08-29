<x-app-layout>
    <x-slot name="header">{{ __('Meine Forderungen') }}</x-slot>

    <div class="mb-4">
        <a href="{{ route('customer.receivables.create') }}" class="text-sm text-white bg-aurevia-navy px-3 py-1.5 rounded-md hover:bg-aurevia-navy/90">
            {{ __('Neue Forderung einreichen') }}
        </a>
    </div>

    <div class="bg-white rounded-lg border border-aurevia-mist overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-aurevia-pearl text-[11px] uppercase text-aurevia-label-gray">
                <tr>
                    <th class="text-left p-3">{{ __('Nummer') }}</th><th class="text-left p-3">{{ __('Rechnung') }}</th>
                    <th class="text-right p-3">{{ __('Betrag') }}</th><th class="text-left p-3">{{ __('Status') }}</th><th class="text-left p-3">{{ __('Eingereicht') }}</th>
                </tr>
            </thead>
            <tbody>
            @forelse($receivables as $r)
                <tr class="border-t border-aurevia-mist/60 hover:bg-aurevia-pearl/50">
                    <td class="p-3"><a class="text-aurevia-navy hover:underline" href="{{ route('customer.receivables.show', $r) }}">{{ $r->receivable_number }}</a></td>
                    <td class="p-3">{{ $r->invoice_number }}</td>
                    <td class="p-3 text-right">{{ eur($r->invoice_amount) }}</td>
                    <td class="p-3">{{ $r->statusLabel() }}</td>
                    <td class="p-3">{{ dmy($r->created_at) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="p-6 text-center text-aurevia-label-gray">{{ __('Noch keine Forderungen eingereicht.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $receivables->links() }}</div>
</x-app-layout>
