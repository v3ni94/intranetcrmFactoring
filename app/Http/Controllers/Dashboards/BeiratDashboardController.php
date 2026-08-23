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

        return view('dashboards.beirat', compact(
            'outstandingPortfolio', 'grossRevenue', 'contributionMargin', 'overdueRatio', 'top10',
            'facilities', 'covenantWarnings', 'newBusinessCount'
        ));
    }
}
