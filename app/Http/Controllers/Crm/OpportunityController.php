<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\Opportunity;
use App\Support\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Http\Request;

class OpportunityController extends Controller
{
    public function index()
    {
        $opportunities = Opportunity::with('lead', 'owner')->latest('id')->paginate(25);
        $pipelineVolume = Opportunity::whereNotIn('stage', ['Gewonnen', 'Verloren'])->sum('expected_volume');

        return view('crm.opportunities.index', compact('opportunities', 'pipelineVolume'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'lead_id' => 'nullable|exists:leads,id',
            'name' => 'required|string|max:255',
            'expected_volume' => 'nullable|numeric|min:0',
            'probability_percent' => 'nullable|integer|min:0|max:100',
        ]);

        $opportunity = Opportunity::create($data + [
            'tenant_id' => TenantContext::id(),
            'stage' => 'Qualifizierung',
            'owner_id' => $request->user()->id,
        ]);

        AuditLogger::log('create', Opportunity::class, $opportunity->id, [], $opportunity->toArray());

        return back()->with('status', 'Opportunity angelegt: '.$opportunity->name);
    }

    public function updateStage(Request $request, Opportunity $opportunity)
    {
        $data = $request->validate(['stage' => 'required|in:'.implode(',', Opportunity::STAGES)]);
        $opportunity->update(['stage' => $data['stage']]);

        AuditLogger::log('update', Opportunity::class, $opportunity->id, [], ['stage' => $opportunity->stage]);

        return back()->with('status', 'Stage aktualisiert.');
    }
}
