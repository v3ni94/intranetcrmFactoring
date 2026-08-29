<x-app-layout>
    <x-slot name="header">{{ __('Onboarding-Leitfaden') }}</x-slot>

    @php $steps = trans('onboarding.steps'); @endphp

    <div x-data="{ step: 0, total: {{ count($steps) }} }" class="max-w-2xl mx-auto">
        {{-- Fortschritt --}}
        <div class="flex items-center gap-2 mb-4">
            <div class="flex-1 bg-aurevia-mist rounded-full h-1.5">
                <div class="bg-aurevia-gold h-1.5 rounded-full transition-all" :style="`width: ${((step + 1) / total) * 100}%`"></div>
            </div>
            <span class="text-xs text-aurevia-label-gray" x-text="`${step + 1} / ${total}`"></span>
        </div>

        @foreach($steps as $i => $s)
            <div x-show="step === {{ $i }}" x-cloak class="bg-white rounded-lg border border-aurevia-mist p-6 min-h-[220px]">
                <div class="text-[11px] uppercase tracking-wide text-aurevia-gold mb-1">{{ __('Schritt') }} {{ $i + 1 }}</div>
                <h2 class="text-lg font-semibold text-aurevia-navy mb-3">{{ __($s['title']) }}</h2>
                <p class="text-sm leading-relaxed">{{ __($s['body']) }}</p>
                @if($s['route'] && \Illuminate\Support\Facades\Route::has($s['route']))
                    @php
                        $navItem = collect(\App\Support\NavigationMenu::forUser(auth()->user()))->flatMap(fn ($g) => $g['items'])->firstWhere('route', $s['route']);
                    @endphp
                    @if($navItem)
                        <a href="{{ route($s['route']) }}" class="inline-block mt-4 text-sm text-aurevia-navy underline hover:no-underline">
                            {{ __('Direkt zum Modul') }} →
                        </a>
                    @endif
                @endif
            </div>
        @endforeach

        <div class="flex items-center justify-between mt-4">
            <button @click="step = Math.max(0, step - 1)" x-show="step > 0" x-cloak
                    class="px-4 py-2 text-sm border border-aurevia-mist rounded-md bg-white hover:bg-aurevia-pearl">← {{ __('Zurück') }}</button>
            <span x-show="step === 0"></span>
            <button @click="step = Math.min(total - 1, step + 1)" x-show="step < total - 1"
                    class="px-4 py-2 text-sm bg-aurevia-navy text-white rounded-md hover:bg-aurevia-navy/90">{{ __('Weiter') }} →</button>
            <a href="{{ route('help.faq') }}" x-show="step === total - 1" x-cloak
               class="px-4 py-2 text-sm bg-aurevia-gold text-white rounded-md hover:opacity-90">{{ __('Zur Hilfe & FAQ') }}</a>
        </div>
    </div>
</x-app-layout>
