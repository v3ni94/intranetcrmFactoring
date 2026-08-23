<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 bg-aurevia-navy border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-aurevia-navy/90 focus:outline-none focus:ring-2 focus:ring-aurevia-gold focus:ring-offset-2 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
