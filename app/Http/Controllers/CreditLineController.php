<?php

namespace App\Http\Controllers;

use App\Models\CreditLine;
use App\Models\Organization;
use App\Services\KpiService;
use App\Support\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Http\Request;

class CreditLineController extends Controller
{
    public function index(KpiService $kpi)
    {
        $lines = CreditLine::with('organization', 'contract')->latest('id')->paginate(25)
            ->through(function (CreditLine $l) use ($kpi) {
                $l->utilization = $kpi->creditLineUtilizationPercent($l);

                return $l;
            });

        $organizations = Organization::customers()->orderBy('name')->get();

        return view('credit-lines.index', compact('lines', 'organizations'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'organization_id' => 'required|exists:organizations,id',
            'contract_id' => 'nullable|exists:contracts,id',
            'line_type' => 'required|in:ankauf,auszahlung,debitor,konzentration',
            'limit_amount' => 'required|numeric|min:0.01',
            'decision_reason' => 'nullable|string|max:500',
        ]);

        $line = CreditLine::create($data + [
            'tenant_id' => TenantContext::id(),
            'status' => 'aktiv',
            'valid_from' => now()->toDateString(),
            'decided_by' => $request->user()->id,
        ]);

        AuditLogger::log('create', CreditLine::class, $line->id, [], $line->toArray(), $data['decision_reason'] ?? null);

        return back()->with('status', 'Kreditlinie angelegt.');
    }
}
