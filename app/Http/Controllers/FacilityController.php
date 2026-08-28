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
            'early_termination_right' => 'nullable|boolean',
            'termination_notice_days' => 'nullable|integer|min:0|max:730',
        ]);

        $facility = Facility::create($data + [
            'tenant_id' => TenantContext::id(),
            'facility_number' => 'FAC-'.now()->format('y').'-'.strtoupper(Str::random(5)),
            'start_date' => now()->toDateString(),
            'status' => 'aktiv',
            'early_termination_right' => $request->boolean('early_termination_right'),
        ]);

        AuditLogger::log('create', Facility::class, $facility->id, [], $facility->toArray());

        return back()->with('status', 'Fazilität angelegt: '.$facility->facility_number);
    }

    /**
     * Kuendigung einer Fazilitaet (v3.00): ordentlich, per Sonderkuendigungsrecht
     * oder wegen Insolvenz des Investors. Der Status wechselt auf 'gekuendigt';
     * die tatsaechliche Rueckfuehrung des gezogenen Kapitals ist ein Treasury-
     * Vorgang und wird ueber das Ereignisprotokoll nachvollziehbar gemacht.
     */
    public function terminate(Request $request, Facility $facility)
    {
        abort_unless(in_array($facility->status, ['aktiv', 'ausgesetzt'], true), 422, 'Fazilität ist nicht aktiv.');

        $data = $request->validate([
            'termination_reason' => 'required|in:ordentlich,sonderkuendigung,insolvenz_investor',
            'note' => 'nullable|string|max:500',
        ]);

        if ($data['termination_reason'] === 'sonderkuendigung' && ! $facility->early_termination_right) {
            return back()->withErrors([
                'termination_reason' => 'Für diese Fazilität ist kein Sonderkündigungsrecht vereinbart.',
            ]);
        }

        $facility->update([
            'status' => 'gekuendigt',
            'terminated_at' => now(),
            'termination_reason' => $data['termination_reason'],
        ]);

        $facility->events()->create([
            'tenant_id' => TenantContext::id(),
            'event_type' => 'kuendigung',
            'event_date' => now()->toDateString(),
            'notes' => 'Kündigung ('.$data['termination_reason'].')'
                .($facility->termination_notice_days ? ', Frist '.$facility->termination_notice_days.' Tage' : '')
                .($data['note'] ? ' — '.$data['note'] : ''),
        ]);

        AuditLogger::log('update', Facility::class, $facility->id,
            ['status' => 'aktiv'], ['status' => 'gekuendigt', 'reason' => $data['termination_reason']],
            $data['note'] ?? null);

        return back()->with('status', "Fazilität {$facility->facility_number} gekündigt ({$data['termination_reason']}).");
    }
}
