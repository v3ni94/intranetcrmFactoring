<x-app-layout>
    <x-slot name="header">{{ $ticket->ticket_number }} · {{ $ticket->subject }}</x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-4">
            @foreach($ticket->messages as $message)
                <div class="bg-white rounded-lg border {{ $message->is_internal_note ? 'border-amber-300 bg-amber-50/50' : 'border-aurevia-mist' }} p-4">
                    <div class="flex items-center justify-between text-xs text-aurevia-label-gray mb-2">
                        <span class="font-medium text-aurevia-navy">{{ $message->user->name ?? '–' }}</span>
                        <span>
                            @if($message->is_internal_note)<span class="text-amber-700 font-semibold mr-2">{{ __('Interne Notiz') }}</span>@endif
                            {{ $message->created_at?->format('d.m.Y H:i') }}
                        </span>
                    </div>
                    <div class="text-sm whitespace-pre-line">{{ $message->body }}</div>
                </div>
            @endforeach

            @if($ticket->status !== 'geschlossen')
                <form method="POST" action="{{ route('tickets.reply', $ticket) }}" class="bg-white rounded-lg border border-aurevia-mist p-4 space-y-3">
                    @csrf
                    <label class="block text-[11px] uppercase text-aurevia-label-gray">{{ __('Antwort') }}</label>
                    <textarea name="body" rows="4" required class="w-full rounded-md border-aurevia-mist text-sm"></textarea>
                    <div class="flex items-center justify-between">
                        @if($isInternal)
                            <label class="text-xs text-aurevia-label-gray flex items-center gap-2">
                                <input type="checkbox" name="is_internal_note" value="1" class="rounded border-aurevia-mist" />
                                {{ __('Interne Notiz (für den Ersteller nicht sichtbar)') }}
                            </label>
                        @else
                            <span></span>
                        @endif
                        <button class="px-4 py-2 bg-aurevia-navy text-white rounded-md text-sm hover:bg-aurevia-navy/90">{{ __('Senden') }}</button>
                    </div>
                </form>
            @else
                <div class="text-sm text-aurevia-label-gray bg-aurevia-pearl rounded-md p-3">{{ __('Dieses Ticket ist geschlossen.') }}</div>
            @endif
        </div>

        <div class="space-y-4">
            <div class="bg-white rounded-lg border border-aurevia-mist p-4 text-sm space-y-2">
                <div><span class="text-aurevia-label-gray">{{ __('Status') }}:</span> {{ str_replace('_', ' ', $ticket->status) }}</div>
                <div><span class="text-aurevia-label-gray">{{ __('Kategorie') }}:</span> {{ $ticket->category }}</div>
                <div><span class="text-aurevia-label-gray">{{ __('Priorität') }}:</span> {{ $ticket->priority }}</div>
                <div><span class="text-aurevia-label-gray">{{ __('Erstellt von') }}:</span> {{ $ticket->creator->name ?? '–' }}</div>
                @if($ticket->organization)<div><span class="text-aurevia-label-gray">{{ __('Organisation') }}:</span> {{ $ticket->organization->name }}</div>@endif
                @if($isInternal)<div><span class="text-aurevia-label-gray">{{ __('Bearbeiter') }}:</span> {{ $ticket->assignee->name ?? '–' }}</div>@endif
                <div><span class="text-aurevia-label-gray">{{ __('Erstellt am') }}:</span> {{ $ticket->created_at?->format('d.m.Y H:i') }}</div>
            </div>

            @if($isInternal)
                <form method="POST" action="{{ route('tickets.status', $ticket) }}" class="bg-white rounded-lg border border-aurevia-mist p-4 space-y-2">
                    @csrf
                    <label class="block text-[11px] uppercase text-aurevia-label-gray">{{ __('Status ändern') }}</label>
                    <select name="status" class="w-full rounded-md border-aurevia-mist text-sm">
                        @foreach(['offen', 'in_bearbeitung', 'beantwortet', 'geschlossen'] as $status)
                            <option value="{{ $status }}" @selected($ticket->status === $status)>{{ str_replace('_', ' ', $status) }}</option>
                        @endforeach
                    </select>
                    <button class="w-full px-4 py-2 bg-aurevia-navy text-white rounded-md text-sm hover:bg-aurevia-navy/90">{{ __('Übernehmen') }}</button>
                </form>
            @endif

            <a href="{{ route('tickets.index') }}" class="block text-sm text-aurevia-navy underline">{{ __('Zurück zur Übersicht') }}</a>
        </div>
    </div>
</x-app-layout>
