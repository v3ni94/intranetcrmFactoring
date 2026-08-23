<?php

namespace App\Http\Controllers\Dashboards;

use App\Http\Controllers\Controller;
use App\Models\Receivable;
use App\Services\KpiService;
use Illuminate\Http\Request;

class KundeDashboardController extends Controller
{
    public function __invoke(Request $request, KpiService $kpi)
    {
        $org = $request->user()->organization;

        if (! $org) {
            return view('dashboards.kunde-no-org');
        }

        $available = $kpi->customerAvailableToday($org->id);
        $payoutMonth = $kpi->customerPayoutSum($org->id, now()->startOfMonth(), now());
        $payoutYear = $kpi->customerPayoutSum($org->id, now()->startOfYear(), now());
        $review = $kpi->customerReceivablesInReview($org->id);
        $actionRequired = $kpi->customerActionRequired($org->id);
        $costs = $kpi->customerCosts($org->id);

        $recent = Receivable::where('organization_id', $org->id)
            ->latest('id')->limit(8)->get();

        $funnel = Receivable::where('organization_id', $org->id)
            ->selectRaw('status, count(*) as c, sum(invoice_amount) as amount')
            ->groupBy('status')->get()->keyBy('status');

        return view('dashboards.kunde', compact('org', 'available', 'payoutMonth', 'payoutYear', 'review', 'actionRequired', 'costs', 'recent', 'funnel'));
    }
}
