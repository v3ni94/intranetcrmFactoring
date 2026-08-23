<x-app-layout>
    <x-slot name="header">Opportunity-Pipeline</x-slot>

    <div class="mb-4">
        <x-kpi-card class="max-w-xs" label="Offenes Pipeline-Volumen" :value="eur($pipelineVolume)" />
    </div>

    <div class="bg-white rounded-lg border border-aurevia-mist overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-aurevia-pearl text-[11px] uppercase text-aurevia-label-gray">
                <tr>
                    <th class="text-left p-3">Name</th><th class="text-right p-3">Volumen</th>
                    <th class="text-right p-3">W'keit</th><th class="text-left p-3">Stage</th><th class="text-left p-3">Nächste Aktion</th>
                </tr>
            </thead>
            <tbody>
            @forelse($opportunities as $o)
                <tr class="border-t border-aurevia-mist/60">
                    <td class="p-3">{{ $o->name }}</td>
                    <td class="p-3 text-right">{{ eur($o->expected_volume) }}</td>
                    <td class="p-3 text-right">{{ $o->probability_percent }}%</td>
                    <td class="p-3">
                        <form method="POST" action="{{ route('crm.opportunities.update-stage', $o) }}">
                            @csrf
                            <select name="stage" onchange="this.form.submit()" class="text-xs rounded-md border-aurevia-mist">
                                @foreach(\App\Models\Opportunity::STAGES as $s)
                                    <option value="{{ $s }}" {{ $o->stage === $s ? 'selected' : '' }}>{{ $s }}</option>
                                @endforeach
                            </select>
                        </form>
                    </td>
                    <td class="p-3">{{ $o->next_action }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="p-6 text-center text-aurevia-label-gray">Keine Opportunities vorhanden.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $opportunities->links() }}</div>
</x-app-layout>
