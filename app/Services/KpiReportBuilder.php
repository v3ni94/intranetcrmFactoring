<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\Receivable;

/**
 * Baut die Kennzahlenuebersicht fuer den E-Mail-Report (v3.00).
 */
class KpiReportBuilder
{
    public function __construct(private KpiService $kpi) {}

    /** @return array<string, string> Label => formatierter Wert */
    public function build(): array
    {
        $openStatuses = ['angekauft', 'zur_auszahlung', 'zahlung_angewiesen', 'ausgezahlt', 'teilbezahlt', 'ueberfaellig'];
        $outstanding = (float) Receivable::whereIn('status', $openStatuses)->sum('invoice_amount');

        return [
            'Angekauft (Monat)' => eur((float) Purchase::where('purchased_at', '>=', now()->startOfMonth())->sum('nominal_amount')),
            'Angekauft (YTD)' => eur((float) Purchase::where('purchased_at', '>=', now()->startOfYear())->sum('nominal_amount')),
            'Ausstehendes Portfolio' => eur($outstanding),
            'Bruttoertrag' => eur($this->kpi->grossRevenue()),
            'Überfälligkeitsquote' => pct($this->kpi->overdueRatioPercent($outstanding)),
            'Top-10-Konzentration' => pct($this->kpi->top10ConcentrationPercent()),
            'DSO' => $this->kpi->averageDso().' Tage',
        ];
    }
}
