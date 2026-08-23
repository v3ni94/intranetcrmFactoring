<?php

namespace App\Http\Controllers\Dashboards;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Services\KpiService;
use Illuminate\Http\Request;

class InvestorDashboardController extends Controller
{
    public function __invoke(Request $request, KpiService $kpi)
    {
        $org = $request->user()->organization;

        if (! $org) {
            return view('dashboards.investor-no-org');
        }

        $facilities = Facility::where('investor_organization_id', $org->id)
            ->with('events')
            ->get()
            ->map(function (Facility $f) use ($kpi) {
                $f->utilization = $kpi->facilityUtilizationPercent($f);

                return $f;
            });

        $totalCommitment = $facilities->sum('commitment_amount');
        $totalDrawn = $facilities->sum('drawn_amount');
        $accruedInterest = $facilities->flatMap->events
            ->where('event_type', 'zinszahlung')
            ->sum('amount');

        return view('dashboards.investor', compact('org', 'facilities', 'totalCommitment', 'totalDrawn', 'accruedInterest'));
    }
}
