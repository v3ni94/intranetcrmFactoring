<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\Receivable;
use App\Support\TenantContext;

/**
 * Berechnet transparent Ankaufsbetrag, Auszahlung, Reserve, Gebuehr und Zinsschaetzung
 * (Abschnitt 11.1). Alle Formeln sind aus den Vertragskonditionen ableitbar.
 */
class PurchaseCalculator
{
    public function calculate(Receivable $receivable): Purchase
    {
        $contract = $receivable->contract;
        $nominal = (float) $receivable->invoice_amount;

        $advanceRate = (float) $contract->advance_rate_percent;
        $reservePercent = (float) $contract->reserve_percent;
        $feePercent = (float) $contract->factoring_fee_percent;

        $purchasable = $nominal;
        $immediatePayout = round($purchasable * ($advanceRate / 100), 2);
        $reserve = round($purchasable * ($reservePercent / 100), 2);
        // Auffuellung, falls Advance-Rate + Reserve wegen Rundung nicht exakt 100% ergeben.
        $reserve = round($purchasable - $immediatePayout, 2);

        $fee = round($purchasable * ($feePercent / 100), 2);

        $expectedDays = (int) ($contract->max_days_outstanding / 2);
        $dailyRate = ((float) ($contract->reference_rate_percent ?? 0) + (float) ($contract->margin_percent ?? 0)) / 100 / 360;
        $expectedInterest = round($immediatePayout * $dailyRate * $expectedDays, 2);

        // Gebuehr und erwarteter Zins mindern die sofortige Auszahlung, nicht den Nominalbetrag.
        $immediatePayout = round($immediatePayout - $fee - $expectedInterest, 2);

        return Purchase::create([
            'tenant_id' => TenantContext::id(),
            'receivable_id' => $receivable->id,
            'nominal_amount' => $nominal,
            'purchasable_amount' => $purchasable,
            'advance_rate_percent' => $advanceRate,
            'immediate_payout_amount' => max(0, $immediatePayout),
            'reserve_amount' => $reserve,
            'factoring_fee_amount' => $fee,
            'expected_interest_amount' => $expectedInterest,
            'deductions_amount' => 0,
            'status' => 'berechnet',
        ]);
    }
}
