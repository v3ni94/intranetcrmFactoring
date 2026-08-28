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

        // Gesamtkapital der Plattform (alle Investoren) zur Einordnung des eigenen Anteils.
        $platformCommitment = (float) Facility::whereIn('status', ['aktiv', 'ausgesetzt'])->sum('commitment_amount');

        // Modellrechnung (v3.00): illustrative Anlage-Staffeln auf Basis der eigenen
        // Zusage (+50%, +100%, +150%) mit kalkulatorischer Monatsmarge. REINE
        // ILLUSTRATION — keine Zusage, keine Prognose, keine Anlageberatung; die
        // Kennzeichnung erfolgt sichtbar in der Oberflaeche.
        $modelMargin = (float) config('aurevia.investor_model_margin_percent');
        $upsellTiers = $totalCommitment > 0
            ? collect([0.5, 1.0, 1.5])->map(fn (float $factor) => [
                'amount' => round((float) $totalCommitment * $factor, -3),
                'model_monthly' => round((float) $totalCommitment * $factor * $modelMargin / 100, 2),
            ])->all()
            : [];

        return view('dashboards.investor', compact(
            'org', 'facilities', 'totalCommitment', 'totalDrawn', 'accruedInterest',
            'platformCommitment', 'upsellTiers', 'modelMargin'
        ));
    }
}
