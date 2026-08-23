<?php

namespace App\Http\Controllers;

use App\Models\KycCase;
use App\Models\Organization;
use App\Services\KpiService;

class RiskController extends Controller
{
    public function index(KpiService $kpi)
    {
        $kycCases = KycCase::with('organization')->latest('id')->paginate(15, ['*'], 'kyc');
        $watchlist = Organization::where('risk_class', 'hoch')->paginate(15, ['*'], 'watchlist');
        $overdueRatio = $kpi->overdueRatioPercent();
        $top10 = $kpi->top10ConcentrationPercent();

        return view('risk.index', compact('kycCases', 'watchlist', 'overdueRatio', 'top10'));
    }
}
