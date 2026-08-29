<x-guest-layout>
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div>
            <x-input-label for="email" value="{{ __('E-Mail-Adresse') }}" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password" value="{{ __('Passwort') }}" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-aurevia-navy shadow-sm focus:ring-aurevia-gold" name="remember">
                <span class="ms-2 text-sm text-gray-600">{{ __('Angemeldet bleiben') }}</span>
            </label>
        </div>

        <div class="flex items-center justify-end mt-4 gap-3">
            @if (Route::has('password.request'))
                <a class="underline text-sm text-gray-600 hover:text-aurevia-navy" href="{{ route('password.request') }}">
                    {{ __('Passwort vergessen?') }}
                </a>
            @endif

            <x-primary-button>{{ __('Anmelden') }}</x-primary-button>
        </div>
    </form>

    @if(config('aurevia.demo_mode'))
    <div class="mt-6 pt-5 border-t border-aurevia-mist">
        <p class="text-[11px] uppercase tracking-wide text-aurevia-label-gray mb-2">{{ __('Demo-Rollenwahl') }}</p>
        <form method="GET" action="{{ route('login') }}" x-data="{}" class="flex gap-2">
            <select onchange="document.getElementById('email').value = this.value; document.getElementById('password').value = '{{ \Database\Seeders\DemoUserSeeder::DEMO_PASSWORD }}';"
                    class="flex-1 text-sm rounded-md border-aurevia-mist">
                <option value="">{{ __('Demo-Zugang wählen …') }}</option>
                @foreach(\App\Support\RoleCatalog::ROLES as $slug => $label)
                    <option value="demo.{{ $slug }}@aurevia-factoring.de">{{ $label }}</option>
                @endforeach
            </select>
        </form>
        <p class="text-[11px] text-aurevia-label-gray mt-2">
            {{ __('Demo-Passwort für alle Zugänge:') }} <code class="bg-aurevia-pearl px-1 py-0.5 rounded">{{ \Database\Seeders\DemoUserSeeder::DEMO_PASSWORD }}</code>
        </p>
    </div>
    @endif
</x-guest-layout>
