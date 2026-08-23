<?php

namespace App\Http\Controllers;

use App\Models\Facility;
use App\Models\Organization;
use App\Services\KpiService;
use App\Support\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FacilityController extends Controller
{
    public function index(KpiService $kpi)
    {
        $facilities = Facility::with('investorOrganization')->latest('id')->get()
            ->map(function (Facility $f) use ($kpi) {
                $f->utilization = $kpi->facilityUtilizationPercent($f);

                return $f;
            });

        $investors = Organization::investors()->orderBy('name')->get();

        return view('facilities.index', compact('facilities', 'investors'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'investor_organization_id' => 'required|exists:organizations,id',
            'name' => 'required|string|max:255',
            'commitment_amount' => 'required|numeric|min:0.01',
            'interest_rate_percent' => 'required|numeric|min:0',
        ]);

        $facility = Facility::create($data + [
            'tenant_id' => TenantContext::id(),
            'facility_number' => 'FAC-'.now()->format('y').'-'.strtoupper(Str::random(5)),
            'start_date' => now()->toDateString(),
            'status' => 'aktiv',
        ]);

        AuditLogger::log('create', Facility::class, $facility->id, [], $facility->toArray());

        return back()->with('status', 'Fazilität angelegt: '.$facility->facility_number);
    }
}
