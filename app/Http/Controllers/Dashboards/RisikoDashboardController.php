<?php

namespace App\Http\Controllers\Dashboards;

use App\Http\Controllers\Controller;
use App\Models\CreditLine;
use App\Models\FacilityEvent;
use App\Models\KycCase;
use App\Models\Organization;
use App\Services\KpiService;

class RisikoDashboardController extends Controller
{
    public function __invoke(KpiService $kpi)
    {
        $openKyc = KycCase::whereIn('result', ['offen'])->count();
        $watchlistOrgs = Organization::where('risk_class', 'hoch')->count();

        $utilizedLines = CreditLine::where('status', 'aktiv')->get()
            ->map(fn ($l) => ['line' => $l, 'utilization' => $kpi->creditLineUtilizationPercent($l)])
            ->sortByDesc('utilization')->take(10);

        $covenantWarnings = FacilityEvent::whereIn('covenant_status', ['warnung', 'verletzt'])
            ->latest('event_date')->limit(10)->get();

        $top10 = $kpi->top10ConcentrationPercent();
        $overdueRatio = $kpi->overdueRatioPercent();
        $ageing = $kpi->ageingBuckets();

        return view('dashboards.risiko', compact('openKyc', 'watchlistOrgs', 'utilizedLines', 'covenantWarnings', 'top10', 'overdueRatio', 'ageing'));
    }
}
