<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <h2 class="text-lg font-medium text-gray-900">Zwei-Faktor-Authentifizierung</h2>
                    <p class="mt-1 text-sm text-gray-600">
                        @if(auth()->user()->hasConfirmedTwoFactor())
                            Aktiv für Ihr Konto.
                        @elseif(auth()->user()->requiresMfa())
                            Für Ihre Rolle verpflichtend, aber noch nicht eingerichtet.
                        @else
                            Für Ihre Rolle nicht vorgeschrieben, kann aber freiwillig aktiviert werden.
                        @endif
                    </p>
                    <a href="{{ route('two-factor.setup') }}" class="mt-3 inline-block text-sm text-white bg-aurevia-navy px-3 py-1.5 rounded-md hover:bg-aurevia-navy/90">
                        Verwalten
                    </a>
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
