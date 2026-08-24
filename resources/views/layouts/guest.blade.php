<!DOCTYPE html>
<html lang="de">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Aurevia Intranet') }}</title>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-aurevia-ink antialiased">
        <div class="min-h-screen flex flex-col bg-aurevia-navy">
            <div class="bg-aurevia-navy text-white text-[11px] tracking-wide py-1.5 px-4 text-center border-b border-white/10">
                <span class="text-aurevia-gold-light font-semibold">ENTWURF – NICHT BESCHLOSSEN</span>
                · Projektgesellschaft in Vorbereitung, Registerangaben folgen nach Gründung
            </div>

            <div class="flex-1 flex flex-col sm:justify-center items-center pt-10 sm:pt-0">
                <div class="text-center mb-6">
                    <div class="text-white text-2xl font-semibold tracking-[0.15em]">AUREVIA <span class="text-aurevia-gold">FACTORING</span></div>
                    <div class="text-aurevia-gold-light italic text-sm mt-1">Liquidität, die weiterbringt.</div>
                    <div class="text-aurevia-mist text-xs mt-0.5 tracking-wide">Factoring &amp; Finance for Healthcare</div>
                </div>

                <div class="w-full sm:max-w-md mt-2 px-8 py-8 bg-white shadow-md overflow-hidden sm:rounded-lg">
                    {{ $slot }}
                </div>

                @if(config('aurevia.demo_mode'))
                <div class="mt-6 max-w-md text-center text-aurevia-mist text-xs px-4">
                    DEMO – ausschließlich fiktive Testdaten – keine echten Zahlungen.
                </div>
                @endif
            </div>

            <footer class="text-[11px] text-aurevia-mist text-center py-4">
                Aurevia Factoring AG (Arbeitsname) · Interne Nutzung · Keine öffentliche Registrierung · v{{ config('aurevia.version') }}
            </footer>
        </div>
    </body>
</html>
