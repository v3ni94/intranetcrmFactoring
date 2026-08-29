<x-app-layout>
    <x-slot name="header">{{ __('Wissensdatenbank') }} · {{ __($title) }}</x-slot>

    <div class="flex flex-wrap gap-2 mb-6">
        <a href="{{ route('help.faq') }}" class="text-xs px-3 py-1.5 rounded-md border border-aurevia-mist bg-white hover:bg-aurevia-pearl">{{ __('Hilfe & FAQ') }}</a>
        <a href="{{ route('help.onboarding') }}" class="text-xs px-3 py-1.5 rounded-md border border-aurevia-mist bg-white hover:bg-aurevia-pearl">{{ __('Onboarding-Leitfaden') }}</a>
        @foreach($docs as $key => $meta)
            <a href="{{ route('help.knowledge', $key) }}"
               class="text-xs px-3 py-1.5 rounded-md border {{ $current === $key ? 'bg-aurevia-navy text-white border-aurevia-navy' : 'border-aurevia-mist bg-white hover:bg-aurevia-pearl' }}">
                {{ __($meta['title']) }}
            </a>
        @endforeach
    </div>

    <div class="bg-white rounded-lg border border-aurevia-mist p-6 overflow-x-auto">
        <div class="knowledge-doc text-sm leading-relaxed max-w-3xl">{!! $html !!}</div>
    </div>

    <style>
        .knowledge-doc h1 { font-size: 1.25rem; font-weight: 600; color: #0E2A47; margin: 0 0 1rem; }
        .knowledge-doc h2 { font-size: 1.05rem; font-weight: 600; color: #0E2A47; margin: 1.5rem 0 0.5rem; }
        .knowledge-doc h3 { font-size: 0.95rem; font-weight: 600; color: #0E2A47; margin: 1.2rem 0 0.4rem; }
        .knowledge-doc p { margin: 0.5rem 0; }
        .knowledge-doc ul, .knowledge-doc ol { padding-left: 1.4rem; margin: 0.5rem 0; list-style: disc; }
        .knowledge-doc ol { list-style: decimal; }
        .knowledge-doc table { border-collapse: collapse; margin: 0.75rem 0; width: 100%; }
        .knowledge-doc th, .knowledge-doc td { border: 1px solid #D9DDE3; padding: 6px 10px; text-align: left; }
        .knowledge-doc th { background: #F5F2EB; font-size: 11px; text-transform: uppercase; color: #8A94A0; }
        .knowledge-doc code { background: #F5F2EB; padding: 1px 4px; border-radius: 3px; font-size: 0.85em; }
        .knowledge-doc hr { border-color: #D9DDE3; margin: 1.25rem 0; }
        .knowledge-doc strong { color: #0E2A47; }
    </style>
</x-app-layout>
