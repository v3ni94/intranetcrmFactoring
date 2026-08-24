<?php

namespace App\Http\Controllers;

use App\Models\ApprovalRequest;
use App\Models\AuditEvent;
use App\Support\TenantContext;

class AuditController extends Controller
{
    public function index()
    {
        // AuditEvent traegt bewusst keinen globalen Tenant-Scope (die Hash-Kette ist
        // mandantenuebergreifend), daher wird die Anzeige hier explizit gefiltert.
        $events = AuditEvent::with('user')
            ->where('tenant_id', TenantContext::id())
            ->latest('id')
            ->paginate(30);
        $approvals = ApprovalRequest::with('requester', 'decider')->latest('id')->limit(15)->get();

        return view('audit.index', compact('events', 'approvals'));
    }
}
