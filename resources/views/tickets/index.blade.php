<x-app-layout>
    <x-slot name="header">{{ __('Support') }}</x-slot>

    {{-- Neues Ticket --}}
    <div class="bg-white rounded-lg border border-aurevia-mist p-4 mb-6">
        <h2 class="text-sm font-semibold text-aurevia-navy mb-3">{{ __('Neues Ticket erstellen') }}</h2>
        <form method="POST" action="{{ route('tickets.store') }}" class="space-y-3 text-sm">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div class="md:col-span-2">
                    <label class="block text-[11px] uppercase text-aurevia-label-gray mb-1">{{ __('Betreff') }}</label>
                    <input name="subject" value="{{ old('subject') }}" required class="w-full rounded-md border-aurevia-mist text-sm" />
                </div>
                <div>
                    <label class="block text-[11px] uppercase text-aurevia-label-gray mb-1">{{ __('Kategorie') }}</label>
                    <select name="category" required class="w-full rounded-md border-aurevia-mist text-sm">
                        <option value="frage">{{ __('Frage') }}</option>
                        <option value="problem">{{ __('Problem') }}</option>
                        <option value="wunsch">{{ __('Wunsch / Anregung') }}</option>
                        <option value="kunde">{{ __('Anliegen Kunde') }}</option>
                        <option value="investor">{{ __('Anliegen Investor') }}</option>
                        <option value="sonstiges">{{ __('Sonstiges') }}</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-[11px] uppercase text-aurevia-label-gray mb-1">{{ __('Beschreibung') }}</label>
                <textarea name="body" rows="3" required class="w-full rounded-md border-aurevia-mist text-sm">{{ old('body') }}</textarea>
            </div>
            <button class="px-4 py-2 bg-aurevia-navy text-white rounded-md text-sm hover:bg-aurevia-navy/90">{{ __('Ticket erstellen') }}</button>
        </form>
    </div>

    {{-- Ticketliste --}}
    <div class="bg-white rounded-lg border border-aurevia-mist overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-aurevia-pearl text-[11px] uppercase text-aurevia-label-gray">
                <tr>
                    <th class="text-left p-3">{{ __('Nr.') }}</th>
                    <th class="text-left p-3">{{ __('Betreff') }}</th>
                    <th class="text-left p-3">{{ __('Kategorie') }}</th>
                    @if($isInternal)<th class="text-left p-3">{{ __('Erstellt von') }}</th>@endif
                    <th class="text-left p-3">{{ __('Status') }}</th>
                    <th class="text-left p-3">{{ __('Aktualisiert') }}</th>
                </tr>
            </thead>
            <tbody>
            @forelse($tickets as $ticket)
                <tr class="border-t border-aurevia-mist/60 hover:bg-aurevia-pearl/40">
                    <td class="p-3 font-mono text-xs"><a href="{{ route('tickets.show', $ticket) }}" class="underline">{{ $ticket->ticket_number }}</a></td>
                    <td class="p-3"><a href="{{ route('tickets.show', $ticket) }}">{{ $ticket->subject }}</a></td>
                    <td class="p-3">{{ $ticket->category }}</td>
                    @if($isInternal)<td class="p-3">{{ $ticket->creator->name ?? '–' }}</td>@endif
                    <td class="p-3">
                        <span class="text-xs px-2 py-0.5 rounded {{ match($ticket->status) { 'offen' => 'bg-amber-100 text-amber-800', 'in_bearbeitung' => 'bg-blue-100 text-blue-800', 'beantwortet' => 'bg-emerald-100 text-emerald-800', default => 'bg-aurevia-pearl' } }}">
                            {{ str_replace('_', ' ', $ticket->status) }}
                        </span>
                    </td>
                    <td class="p-3">{{ $ticket->updated_at?->format('d.m.Y H:i') }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="p-6 text-center text-aurevia-label-gray">{{ __('Keine Tickets vorhanden.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
        <div class="p-3">{{ $tickets->links() }}</div>
    </div>
</x-app-layout>
