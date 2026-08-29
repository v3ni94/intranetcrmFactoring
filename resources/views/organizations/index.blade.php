<x-app-layout>
    <x-slot name="header">{{ __('Kunden') }}</x-slot>

    <div class="bg-white rounded-lg border border-aurevia-mist overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-aurevia-pearl text-[11px] uppercase text-aurevia-label-gray">
                <tr>
                    <th class="text-left p-3">{{ __('Name') }}</th><th class="text-left p-3">{{ __('Fachrichtung') }}</th>
                    <th class="text-left p-3">{{ __('Ort') }}</th><th class="text-left p-3">{{ __('Status') }}</th><th class="text-left p-3">{{ __('Risikoklasse') }}</th><th class="p-3"></th>
                </tr>
            </thead>
            <tbody>
            @forelse($organizations as $org)
                <tr class="border-t border-aurevia-mist/60">
                    <td class="p-3">{{ $org->name }}</td>
                    <td class="p-3">{{ $org->specialty }}</td>
                    <td class="p-3">{{ $org->city }}</td>
                    <td class="p-3">{{ $org->customer_status }}</td>
                    <td class="p-3 capitalize">{{ $org->risk_class ?? '–' }}</td>
                    <td class="p-3 text-right"><a href="{{ route('organizations.show', $org) }}" class="text-aurevia-navy hover:underline">{{ __('Öffnen') }}</a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="p-6 text-center text-aurevia-label-gray">{{ __('Keine Kunden vorhanden.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $organizations->links() }}</div>
</x-app-layout>
