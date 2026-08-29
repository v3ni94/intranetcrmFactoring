<x-app-layout>
    <x-slot name="header">{{ __('Controlling & Kosten') }}</x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div class="bg-white rounded-lg border border-aurevia-mist p-4">
            <h2 class="text-sm font-semibold text-aurevia-navy mb-3">{{ __('Kosten je Monat (letzte 6 Monate)') }}</h2>
            <x-bar-chart chart-id="costsChart"
                :labels="$monthly->pluck('label')->all()"
                :values="$monthly->pluck('value')->all()" />
        </div>
        <div class="bg-white rounded-lg border border-aurevia-mist p-4">
            <h2 class="text-sm font-semibold text-aurevia-navy mb-3">{{ __('Kosten nach Kategorie (laufendes Jahr)') }}</h2>
            <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <tbody>
                @forelse($byCategory as $row)
                    <tr class="border-t border-aurevia-mist/60">
                        <td class="py-1.5">{{ $categories[$row->category] ?? $row->category }}</td>
                        <td class="py-1.5 text-right font-medium">{{ eur($row->total) }}</td>
                    </tr>
                @empty
                    <tr><td class="py-4 text-center text-aurevia-label-gray">{{ __('Noch keine Kosten erfasst.') }}</td></tr>
                @endforelse
                </tbody>
            </table>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg border border-aurevia-mist p-4 mb-6">
        <h2 class="text-sm font-semibold text-aurevia-navy mb-3">{{ __('Kostenposition erfassen') }}</h2>
        <form method="POST" action="{{ route('costs.store') }}" class="grid grid-cols-1 md:grid-cols-5 gap-3 text-sm">
            @csrf
            <div>
                <label class="block text-[11px] uppercase text-aurevia-label-gray mb-1">{{ __('Datum') }}</label>
                <input type="date" name="cost_date" value="{{ old('cost_date', now()->toDateString()) }}" required class="w-full rounded-md border-aurevia-mist text-sm" />
            </div>
            <div>
                <label class="block text-[11px] uppercase text-aurevia-label-gray mb-1">{{ __('Kategorie') }}</label>
                <select name="category" required class="w-full rounded-md border-aurevia-mist text-sm">
                    @foreach($categories as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="block text-[11px] uppercase text-aurevia-label-gray mb-1">{{ __('Beschreibung') }}</label>
                <input name="description" value="{{ old('description') }}" required class="w-full rounded-md border-aurevia-mist text-sm" />
            </div>
            <div>
                <label class="block text-[11px] uppercase text-aurevia-label-gray mb-1">{{ __('Betrag (EUR)') }}</label>
                <div class="flex gap-2">
                    <input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount') }}" required class="w-full rounded-md border-aurevia-mist text-sm" />
                    <button class="px-4 py-2 bg-aurevia-navy text-white rounded-md text-sm hover:bg-aurevia-navy/90">{{ __('Erfassen') }}</button>
                </div>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-lg border border-aurevia-mist overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-aurevia-pearl text-[11px] uppercase text-aurevia-label-gray">
                <tr>
                    <th class="text-left p-3">{{ __('Datum') }}</th>
                    <th class="text-left p-3">{{ __('Kategorie') }}</th>
                    <th class="text-left p-3">{{ __('Beschreibung') }}</th>
                    <th class="text-right p-3">{{ __('Betrag') }}</th>
                    <th class="text-left p-3">{{ __('Erfasst von') }}</th>
                    <th class="text-right p-3"></th>
                </tr>
            </thead>
            <tbody>
            @forelse($costs as $cost)
                <tr class="border-t border-aurevia-mist/60">
                    <td class="p-3">{{ $cost->cost_date?->format('d.m.Y') }}</td>
                    <td class="p-3">{{ $categories[$cost->category] ?? $cost->category }}</td>
                    <td class="p-3">{{ $cost->description }}</td>
                    <td class="p-3 text-right font-medium">{{ eur($cost->amount) }}</td>
                    <td class="p-3">{{ $cost->creator->name ?? '–' }}</td>
                    <td class="p-3 text-right">
                        <form method="POST" action="{{ route('costs.destroy', $cost) }}" class="inline">
                            @csrf
                            <button class="text-xs text-red-700 underline hover:no-underline"
                                onclick="return confirm('{{ __('Kostenposition wirklich löschen?') }}')">{{ __('Löschen') }}</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="p-6 text-center text-aurevia-label-gray">{{ __('Noch keine Kosten erfasst.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
        <div class="p-3">{{ $costs->links() }}</div>
    </div>
</x-app-layout>
