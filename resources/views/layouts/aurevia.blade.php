<!DOCTYPE html>
<html lang="de" x-data="{ sidebarOpen: true }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Aurevia Intranet' }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-aurevia-pearl text-aurevia-ink">

    {{-- Rechtlicher Statusbanner: solange keine Handelsregistereintragung vorliegt --}}
    <div class="bg-aurevia-navy text-white text-xs tracking-wide py-1.5 px-4 flex flex-wrap items-center justify-center gap-2 text-center">
        <span class="font-semibold text-aurevia-gold-light">ENTWURF – NICHT BESCHLOSSEN</span>
        <span class="text-aurevia-mist">·</span>
        <span>Aurevia Factoring AG – Projektgesellschaft in Vorbereitung, Registerangaben folgen nach Gründung</span>
    </div>
    @if(config('aurevia.demo_mode'))
    <div class="bg-amber-500 text-aurevia-ink text-xs font-semibold tracking-wide py-1.5 px-4 text-center">
        DEMO – ausschließlich fiktive Testdaten – keine echten Zahlungen
    </div>
    @endif

    <div class="flex min-h-screen">
        {{-- Linke Navigation --}}
        <aside :class="sidebarOpen ? 'w-64' : 'w-16'" class="bg-aurevia-navy text-white flex-shrink-0 transition-all duration-150 ease-in-out">
            <div class="h-16 flex items-center justify-between px-4 border-b border-white/10">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2 overflow-hidden">
                    <span class="inline-block w-2.5 h-2.5 rounded-full bg-aurevia-gold flex-shrink-0"></span>
                    <span x-show="sidebarOpen" class="font-semibold tracking-wide whitespace-nowrap">AUREVIA FACTORING</span>
                </a>
                <button @click="sidebarOpen = !sidebarOpen" class="text-aurevia-mist hover:text-white" aria-label="Navigation ein-/ausklappen">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" /></svg>
                </button>
            </div>
            <nav class="py-3 space-y-0.5 text-sm">
                @auth
                    @foreach(\App\Support\NavigationMenu::forUser(auth()->user()) as $item)
                        <a href="{{ route($item['route']) }}"
                           class="flex items-center gap-3 px-4 py-2 hover:bg-white/10 {{ request()->routeIs($item['route']) ? 'bg-white/10 border-l-2 border-aurevia-gold' : 'border-l-2 border-transparent' }}">
                            <span class="w-1.5 h-1.5 rounded-full bg-aurevia-gold flex-shrink-0"></span>
                            <span x-show="sidebarOpen" class="whitespace-nowrap">{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                @endauth
            </nav>
        </aside>

        <div class="flex-1 flex flex-col min-w-0">
            {{-- Obere Arbeitsleiste --}}
            <header class="h-16 bg-white border-b border-aurevia-mist flex items-center justify-between px-6 gap-4">
                <div class="flex-1 max-w-md">
                    <input type="search" placeholder="Suche: Kunde, Forderung, Vertrag …"
                           class="w-full text-sm rounded-md border-aurevia-mist focus:border-aurevia-gold focus:ring-aurevia-gold" />
                </div>
                <div class="flex items-center gap-4 text-sm">
                    @auth
                        <span class="text-aurevia-label-gray uppercase tracking-wide text-[11px]">{{ auth()->user()->primaryRoleLabel() }}</span>
                        <span class="font-medium">{{ auth()->user()->name }}</span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="text-aurevia-navy hover:text-aurevia-gold font-medium">Abmelden</button>
                        </form>
                    @endauth
                </div>
            </header>

            @if(isset($breadcrumbs))
            <div class="px-6 pt-4 text-xs text-aurevia-label-gray">
                {{ $breadcrumbs }}
            </div>
            @endif

            <main class="flex-1 p-6">
                @if(session('status'))
                    <div class="mb-4 rounded-md bg-green-50 border border-green-200 text-green-800 text-sm px-4 py-3">
                        {{ session('status') }}
                    </div>
                @endif
                @if(session('error'))
                    <div class="mb-4 rounded-md bg-red-50 border border-red-200 text-red-800 text-sm px-4 py-3">
                        {{ session('error') }}
                    </div>
                @endif

                @if(isset($header))
                    <div class="mb-6 flex items-center justify-between">
                        <h1 class="text-xl font-semibold text-aurevia-navy">{{ $header }}</h1>
                        <div class="w-16 h-0.5 bg-aurevia-gold hidden sm:block"></div>
                    </div>
                @endif

                {{ $slot }}
            </main>

            <footer class="text-[11px] text-aurevia-label-gray px-6 py-3 border-t border-aurevia-mist">
                Aurevia Factoring AG (Arbeitsname) · Projektgesellschaft in Vorbereitung · Registerangaben folgen nach Gründung ·
                Interne Nutzung · Alle Kennzahlen ohne Gewähr, Modellrechnungen sind keine Zusage.
            </footer>
        </div>
    </div>
</body>
</html>
