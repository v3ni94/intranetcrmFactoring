<x-app-layout>
    <x-slot name="header">Anträge &amp; Onboarding</x-slot>

    <div class="flex gap-4 overflow-x-auto pb-4">
        @foreach($stages as $stage)
            <div class="min-w-[220px] bg-white rounded-lg border border-aurevia-mist p-3 flex-shrink-0">
                <h3 class="text-[11px] uppercase tracking-wide text-aurevia-label-gray mb-2">{{ $stage }} ({{ ($leadsByStage->get($stage) ?? collect())->count() }})</h3>
                @foreach($leadsByStage->get($stage) ?? [] as $lead)
                    <div class="text-sm bg-aurevia-pearl rounded-md p-2 mb-2">{{ $lead->company_name }}</div>
                @endforeach
            </div>
        @endforeach
    </div>
</x-app-layout>
