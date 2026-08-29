<?php

namespace App\Http\Controllers;

use App\Models\OperatingCost;
use App\Support\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Http\Request;

/**
 * Controlling (v3.00): einfache Kostenerfassung fuer die Kostensicht der
 * Gesellschaft (Personal, IT, Refinanzierung, ...). Zugriff: Controlling,
 * Treasury, Geschaeftsleitung, Superadmin (Route-Middleware).
 */
class CostController extends Controller
{
    public function index()
    {
        $costs = OperatingCost::with('creator')->orderByDesc('cost_date')->paginate(25);

        // Monatssummen der letzten 6 Monate fuer das Diagramm
        $monthly = collect(range(5, 0))->map(function (int $monthsAgo) {
            $start = now()->subMonths($monthsAgo)->startOfMonth();

            return [
                'label' => $start->format('m/Y'),
                'value' => (float) OperatingCost::whereBetween('cost_date', [$start, $start->copy()->endOfMonth()])->sum('amount'),
            ];
        });

        $byCategory = OperatingCost::whereBetween('cost_date', [now()->startOfYear(), now()])
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();

        return view('costs.index', [
            'costs' => $costs,
            'monthly' => $monthly,
            'byCategory' => $byCategory,
            'categories' => OperatingCost::CATEGORIES,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'cost_date' => 'required|date',
            'category' => 'required|in:'.implode(',', array_keys(OperatingCost::CATEGORIES)),
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
        ]);

        $cost = OperatingCost::create($data + [
            'tenant_id' => TenantContext::id(),
            'created_by' => $request->user()->id,
        ]);

        AuditLogger::log('create', OperatingCost::class, $cost->id, [], $cost->toArray());

        return back()->with('status', __('Kostenposition erfasst.'));
    }

    public function destroy(Request $request, OperatingCost $cost)
    {
        AuditLogger::log('delete', OperatingCost::class, $cost->id, $cost->toArray(), [], 'Kostenposition gelöscht');
        $cost->delete();

        return back()->with('status', __('Kostenposition gelöscht.'));
    }
}
