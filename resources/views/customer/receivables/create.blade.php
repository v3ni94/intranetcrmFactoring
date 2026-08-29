<x-app-layout>
    <x-slot name="header">{{ __('Neue Forderung einreichen') }}</x-slot>

    <div class="max-w-2xl">
        <ol class="flex text-[11px] uppercase tracking-wide text-aurevia-label-gray mb-6 gap-2">
            <li class="px-2 py-1 rounded {{ session('preview') ? '' : 'bg-aurevia-navy text-white' }}">{{ __('1. Daten erfassen') }}</li>
            <li class="px-2 py-1 rounded {{ session('preview') ? 'bg-aurevia-navy text-white' : '' }}">{{ __('2. Angaben prüfen') }}</li>
            <li class="px-2 py-1 rounded">{{ __('3. Bestätigen') }}</li>
        </ol>

        @if(!session('preview'))
        <form method="POST" action="{{ route('customer.receivables.preview') }}" class="bg-white rounded-lg border border-aurevia-mist p-6 space-y-4">
            @csrf
            <div>
                <x-input-label for="contract_id" value="{{ __('Factoringvertrag') }}" />
                <select id="contract_id" name="contract_id" class="mt-1 w-full rounded-md border-aurevia-mist" required>
                    @foreach($contracts as $c)
                        <option value="{{ $c->id }}" {{ old('contract_id') == $c->id ? 'selected' : '' }}>{{ $c->contract_number }} · {{ __('Advance Rate') }} {{ $c->advance_rate_percent }}%</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="invoice_number" value="{{ __('Rechnungsnummer') }}" />
                <x-text-input id="invoice_number" name="invoice_number" class="mt-1 w-full" :value="old('invoice_number')" required />
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <x-input-label for="invoice_date" value="{{ __('Rechnungsdatum') }}" />
                    <x-text-input type="date" id="invoice_date" name="invoice_date" class="mt-1 w-full" :value="old('invoice_date')" required />
                </div>
                <div>
                    <x-input-label for="invoice_amount" value="{{ __('Rechnungsbetrag (EUR)') }}" />
                    <x-text-input type="number" step="0.01" id="invoice_amount" name="invoice_amount" class="mt-1 w-full" :value="old('invoice_amount')" required />
                </div>
            </div>
            <p class="text-[11px] text-aurevia-label-gray">{{ __('Unterstützte Wege im Prototyp: manuelle Erfassung. Datei-Upload (PDF/CSV/XLSX/XML) und Praxissoftware-Anbindung sind als Adapter vorgesehen, siehe Roadmap.') }}</p>
            <x-primary-button>{{ __('Weiter zur Prüfung') }}</x-primary-button>
        </form>
        @else
        @php($p = session('preview'))
        <div class="bg-white rounded-lg border border-aurevia-mist p-6 space-y-3">
            <h2 class="text-sm font-semibold text-aurevia-navy">{{ __('Angaben prüfen') }}</h2>
            <div class="grid grid-cols-2 gap-2 text-sm">
                <div class="text-aurevia-label-gray">{{ __('Rechnungsbetrag') }}</div><div class="text-right font-medium">{{ eur($p['nominal']) }}</div>
                <div class="text-aurevia-label-gray">{{ __('Voraussichtliche Auszahlungsquote') }}</div><div class="text-right">{{ pct($p['advance_rate']) }}</div>
                <div class="text-aurevia-label-gray">{{ __('Voraussichtliche Auszahlung') }}</div><div class="text-right font-semibold text-aurevia-navy">{{ eur($p['immediate_payout']) }}</div>
                <div class="text-aurevia-label-gray">{{ __('Sicherheitseinbehalt') }}</div><div class="text-right">{{ eur($p['reserve']) }}</div>
                <div class="text-aurevia-label-gray">{{ __('Voraussichtliche Gebühr') }}</div><div class="text-right">{{ eur($p['fee']) }}</div>
            </div>
            <p class="text-[11px] text-aurevia-label-gray">{{ __('Hinweis: Die finale Prüfung kann abweichen, insbesondere bei Rückfragen zur Regelprüfung.') }}</p>

            <form method="POST" action="{{ route('customer.receivables.store') }}" class="pt-3 border-t border-aurevia-mist">
                @csrf
                <input type="hidden" name="contract_id" value="{{ $p['data']['contract_id'] }}">
                <input type="hidden" name="invoice_number" value="{{ $p['data']['invoice_number'] }}">
                <input type="hidden" name="invoice_date" value="{{ $p['data']['invoice_date'] }}">
                <input type="hidden" name="invoice_amount" value="{{ $p['data']['invoice_amount'] }}">
                <input type="hidden" name="due_date" value="{{ \Illuminate\Support\Carbon::parse($p['data']['invoice_date'])->addDays(30)->toDateString() }}">
                <x-primary-button>{{ __('Forderung verbindlich einreichen') }}</x-primary-button>
                <a href="{{ route('customer.receivables.create') }}" class="ml-3 text-sm text-aurevia-label-gray hover:text-aurevia-navy">{{ __('Zurück') }}</a>
            </form>
        </div>
        @endif
    </div>
</x-app-layout>
