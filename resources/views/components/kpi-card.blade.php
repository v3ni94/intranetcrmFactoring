@props(['label', 'value', 'period' => null, 'formula' => null, 'trend' => null, 'tone' => 'neutral'])
@php
    $toneClasses = [
        'neutral' => 'border-aurevia-mist',
        'good' => 'border-emerald-400',
        'warn' => 'border-amber-400',
        'bad' => 'border-red-400',
    ][$tone] ?? 'border-aurevia-mist';
@endphp
<div {{ $attributes->merge(['class' => "bg-white rounded-lg border {$toneClasses} p-4 flex flex-col gap-1"]) }}>
    <div class="flex items-center justify-between">
        <span class="text-[11px] uppercase tracking-wide text-aurevia-label-gray">{{ $label }}</span>
        @if($trend)
            <span class="text-[11px] {{ str_starts_with($trend, '-') ? 'text-red-600' : 'text-emerald-600' }}">{{ $trend }}</span>
        @endif
    </div>
    <div class="text-2xl font-semibold text-aurevia-navy">{{ $value }}</div>
    <div class="flex items-center justify-between text-[11px] text-aurevia-label-gray">
        <span>{{ $period ?? 'Stand: heute' }}</span>
        @if($formula)
            <span class="group relative cursor-help underline decoration-dotted">
                Formel
                <span class="hidden group-hover:block absolute right-0 z-10 mt-1 w-56 bg-aurevia-navy text-white text-[11px] p-2 rounded shadow-lg normal-case">{{ $formula }}</span>
            </span>
        @endif
    </div>
</div>
