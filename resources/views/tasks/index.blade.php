<x-app-layout>
    <x-slot name="header">{{ __('Aufgaben') }}</x-slot>

    <div class="bg-white rounded-lg border border-aurevia-mist p-4 mb-6">
        <form method="POST" action="{{ route('tasks.store') }}" class="flex flex-wrap gap-3">
            @csrf
            <x-text-input name="title" placeholder="{{ __('Neue Aufgabe') }}" class="flex-1 min-w-[200px]" required />
            <x-text-input type="date" name="due_date" class="w-40" />
            <select name="priority" class="text-sm rounded-md border-aurevia-mist">
                <option value="normal">{{ __('Normal') }}</option>
                <option value="niedrig">{{ __('Niedrig') }}</option>
                <option value="hoch">{{ __('Hoch') }}</option>
            </select>
            <x-primary-button>{{ __('Anlegen') }}</x-primary-button>
        </form>
    </div>

    <div class="bg-white rounded-lg border border-aurevia-mist overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-aurevia-pearl text-[11px] uppercase text-aurevia-label-gray">
                <tr><th class="text-left p-3">{{ __('Titel') }}</th><th class="text-left p-3">{{ __('Fällig') }}</th><th class="text-left p-3">{{ __('Zuständig') }}</th><th class="text-left p-3">{{ __('Status') }}</th><th class="p-3"></th></tr>
            </thead>
            <tbody>
            @forelse($tasks as $t)
                <tr class="border-t border-aurevia-mist/60 {{ $t->status === 'erledigt' ? 'opacity-50' : '' }}">
                    <td class="p-3">{{ $t->title }}</td>
                    <td class="p-3">{{ dmy($t->due_date) }}</td>
                    <td class="p-3">{{ $t->assignee->name ?? '–' }}</td>
                    <td class="p-3 capitalize">{{ str_replace('_',' ', $t->status) }}</td>
                    <td class="p-3 text-right">
                        @if($t->status !== 'erledigt')
                        <form method="POST" action="{{ route('tasks.complete', $t) }}">@csrf<button class="text-aurevia-navy hover:underline">{{ __('Erledigt') }}</button></form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="p-6 text-center text-aurevia-label-gray">{{ __('Keine Aufgaben vorhanden.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $tasks->links() }}</div>
</x-app-layout>
