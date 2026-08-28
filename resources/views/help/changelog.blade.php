<x-app-layout>
    <x-slot name="header">{{ __('Changelog / Chronologie') }}</x-slot>

    <p class="text-sm text-aurevia-label-gray mb-6">
        {{ __('Alle Änderungen und Verbesserungen der Plattform mit Version, Zeitstempel und Verantwortlichem. Versionsschema: die erste Zahl steigt bei größeren Umbauten, die zweite bei Erweiterungen und Fehlerbehebungen.') }}
    </p>

    <div class="space-y-6">
        @foreach($entries as $entry)
            <div class="bg-white rounded-lg border border-aurevia-mist p-4">
                <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                    <div class="flex items-center gap-3">
                        <span class="text-sm font-semibold text-white bg-aurevia-navy px-2.5 py-0.5 rounded">v{{ $entry['version'] }}</span>
                        <span class="text-xs text-aurevia-label-gray">{{ $entry['date'] }} {{ __('Uhr') }}</span>
                    </div>
                    <span class="text-xs text-aurevia-label-gray">{{ __('Programmierung') }}: {{ $entry['author'] }}</span>
                </div>
                <ul class="text-sm space-y-1.5 list-disc pl-5">
                    @foreach($entry['changes'] as $change)
                        <li>{{ $change }}</li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </div>
</x-app-layout>
