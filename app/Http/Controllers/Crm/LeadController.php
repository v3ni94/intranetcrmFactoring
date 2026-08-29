<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Support\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    public function index()
    {
        $leads = Lead::with('owner')->latest('id')->paginate(25);
        $statuses = Lead::STATUSES;

        return view('crm.leads.index', compact('leads', 'statuses'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'company_name' => 'required|string|max:255',
            'specialty' => 'nullable|string|max:255',
            'contact_name' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:100',
            'source' => 'nullable|string|max:255',
        ]);

        $lead = Lead::create($data + [
            'tenant_id' => TenantContext::id(),
            'status' => 'Lead',
            'owner_id' => $request->user()->id,
        ]);

        AuditLogger::log('create', Lead::class, $lead->id, [], $lead->toArray());

        return back()->with('status', __('Lead angelegt: :name', ['name' => $lead->company_name]));
    }

    public function updateStatus(Request $request, Lead $lead)
    {
        $data = $request->validate(['status' => 'required|in:'.implode(',', Lead::STATUSES)]);
        $old = $lead->status;
        $lead->update(['status' => $data['status']]);

        AuditLogger::log('update', Lead::class, $lead->id, ['status' => $old], ['status' => $lead->status]);

        return back()->with('status', __('Lead-Status geändert: :old → :new', ['old' => $old, 'new' => $lead->status]));
    }
}
