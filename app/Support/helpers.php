<?php

use Illuminate\Support\Carbon;

if (! function_exists('eur')) {
    function eur(float|int|string|null $amount): string
    {
        return number_format((float) $amount, 2, ',', '.').' EUR';
    }
}

if (! function_exists('pct')) {
    function pct(float|int|string|null $value): string
    {
        return number_format((float) $value, 2, ',', '.').' %';
    }
}

if (! function_exists('dmy')) {
    function dmy(mixed $date): string
    {
        if (! $date) {
            return '–';
        }

        return Carbon::parse($date)->format('d.m.Y');
    }
}
