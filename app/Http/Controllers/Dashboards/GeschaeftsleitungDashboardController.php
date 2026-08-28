<?php

namespace App\Http\Controllers\Dashboards;

use App\Http\Controllers\Controller;
use App\Models\FinancialScenario;
use App\Models\Purchase;
use App\Models\Receivable;
use App\Services\KpiService;
use Illuminate\Support\Carbon;

class GeschaeftsleitungDashboardController extends Controller
{
    public function __invoke(KpiService $kpi)
    {
        $purchasedMonth = (float) Purchase::where('purchased_at', '>=', now()->startOfMonth())->sum('nominal_amount');
        $purchasedYtd = (float) Purchase::where('purchased_at', '>=', now()->startOfYear())->sum('nominal_amount');
        $purchasedTwelveMonths = (float) Purchase::where('purchased_at', '>=', Carbon::now()->subMonths(12))->sum('nominal_amount');

        $outstandingPortfolio = (float) Receivable::whereIn('status', ['angekauft', 'zur_auszahlung', 'zahlung_angewiesen', 'ausgezahlt', 'teilbezahlt', 'ueberfaellig'])
            ->sum('invoice_amount');

        $grossRevenue = $kpi->grossRevenue();
        $refinancingCost = $kpi->refinancingCost();
        $contributionMargin = $kpi->contributionMargin();
        $dilution = $kpi->dilutionRatePercent();
        $overdueRatio = $kpi->overdueRatioPercent($outstandingPortfolio);
        $top10 = $kpi->top10ConcentrationPercent();
        $dso = $kpi->averageDso();
        $ageing = $kpi->ageingBuckets();

        $scenarioOrder = ['konservativ' => 0, 'base' => 1, 'wachstum' => 2, 'stress' => 3];
        $scenarios = FinancialScenario::all()->sortBy(fn ($s) => $scenarioOrder[$s->scenario_key] ?? 99)->values();

        // Ankaufsvolumen je Monat (letzte 6 Monate) fuer das Balkendiagramm.
        $monthlyPurchases = collect(range(5, 0))->map(function (int $monthsAgo) {
            $start = now()->subMonths($monthsAgo)->startOfMonth();

            return [
                'label' => $start->format('m/Y'),
                'value' => (float) Purchase::whereBetween('purchased_at', [$start, $start->copy()->endOfMonth()])->sum('nominal_amount'),
            ];
        });

        return view('dashboards.geschaeftsleitung', compact(
            'purchasedMonth', 'purchasedYtd', 'purchasedTwelveMonths', 'outstandingPortfolio',
            'grossRevenue', 'refinancingCost', 'contributionMargin', 'dilution', 'overdueRatio',
            'top10', 'dso', 'ageing', 'scenarios', 'monthlyPurchases'
        ));
    }
}
