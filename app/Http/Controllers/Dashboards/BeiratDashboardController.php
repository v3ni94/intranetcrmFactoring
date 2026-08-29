<?php

namespace App\Http\Controllers\Dashboards;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\FacilityEvent;
use App\Models\Purchase;
use App\Models\Receivable;
use App\Services\KpiService;

class BeiratDashboardController extends Controller
{
    public function __invoke(KpiService $kpi)
    {
        $outstandingPortfolio = (float) Receivable::whereIn('status', ['angekauft', 'zur_auszahlung', 'zahlung_angewiesen', 'ausgezahlt', 'teilbezahlt', 'ueberfaellig'])
            ->sum('invoice_amount');

        $grossRevenue = $kpi->grossRevenue();
        $contributionMargin = $kpi->contributionMargin();
        $overdueRatio = $kpi->overdueRatioPercent();
        $top10 = $kpi->top10ConcentrationPercent();

        $facilities = Facility::with('investorOrganization')->get();
        $covenantWarnings = FacilityEvent::whereIn('covenant_status', ['warnung', 'verletzt'])->count();
        $newBusinessCount = Purchase::where('purchased_at', '>=', now()->subDays(30))->count();

        // v3.03: Ankaufsvolumen der letzten 12 Monate fuer die grafische Sicht
        $volumeLabels = [];
        $volumeValues = [];
        for ($m = 11; $m >= 0; $m--) {
            $start = now()->copy()->subMonths($m)->startOfMonth();
            $volumeLabels[] = $start->format('m/y');
            $volumeValues[] = (float) Purchase::whereBetween('purchased_at', [$start, $start->copy()->endOfMonth()])->sum('nominal_amount');
        }

        return view('dashboards.beirat', compact(
            'outstandingPortfolio', 'grossRevenue', 'contributionMargin', 'overdueRatio', 'top10',
            'facilities', 'covenantWarnings', 'newBusinessCount', 'volumeLabels', 'volumeValues'
        ));
    }
}
