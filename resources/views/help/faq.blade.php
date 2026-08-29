<x-app-layout>
    <x-slot name="header">{{ __('Hilfe & FAQ') }}</x-slot>

    <p class="text-sm text-aurevia-label-gray mb-4">{{ __('faq.intro') }}</p>

    {{-- Wissensdatenbank & Onboarding (v3.02) --}}
    <div class="flex flex-wrap gap-2 mb-6">
        <a href="{{ route('help.onboarding') }}" class="text-xs px-3 py-1.5 rounded-md bg-aurevia-navy text-white hover:bg-aurevia-navy/90">{{ __('Onboarding-Leitfaden') }}</a>
        <a href="{{ route('help.knowledge', 'handbuch') }}" class="text-xs px-3 py-1.5 rounded-md border border-aurevia-mist bg-white hover:bg-aurevia-pearl">{{ __('Benutzerhandbuch') }}</a>
        @if(auth()->user()->hasAnyRole(\App\Support\RoleCatalog::INTERNAL_ROLES))
            <a href="{{ route('help.knowledge', 'prozesse') }}" class="text-xs px-3 py-1.5 rounded-md border border-aurevia-mist bg-white hover:bg-aurevia-pearl">{{ __('Prozessleitfaden') }}</a>
            <a href="{{ route('help.knowledge', 'prozesshandbuch') }}" class="text-xs px-3 py-1.5 rounded-md border border-aurevia-mist bg-white hover:bg-aurevia-pearl">{{ __('Prozesshandbuch (BaFin-orientiert)') }}</a>
            <a href="{{ route('help.knowledge', 'bafin') }}" class="text-xs px-3 py-1.5 rounded-md border border-aurevia-mist bg-white hover:bg-aurevia-pearl">{{ __('BaFin-Vorbereitungsdokumentation') }}</a>
            <a href="{{ route('help.knowledge', 'datenschutz') }}" class="text-xs px-3 py-1.5 rounded-md border border-aurevia-mist bg-white hover:bg-aurevia-pearl">{{ __('Datenschutzkonzept') }}</a>
        @endif
    </div>

    <div class="space-y-3" x-data="{ open: null }">
        @foreach(trans('faq.items') as $i => $faq)
            <div class="bg-white rounded-lg border border-aurevia-mist">
                <button @click="open = open === {{ $i }} ? null : {{ $i }}"
                        class="w-full flex items-center justify-between p-4 text-left text-sm font-medium text-aurevia-navy">
                    <span>{{ $faq['q'] }}</span>
                    <span class="text-aurevia-gold text-lg" x-text="open === {{ $i }} ? '–' : '+'"></span>
                </button>
                <div x-show="open === {{ $i }}" x-cloak class="px-4 pb-4 text-sm whitespace-pre-line text-aurevia-ink/90 border-t border-aurevia-mist/60 pt-3">{{ $faq['a'] }}</div>
            </div>
        @endforeach
    </div>

    <p class="text-[11px] text-aurevia-label-gray mt-6">{{ __('faq.disclaimer') }}</p>
</x-app-layout>
