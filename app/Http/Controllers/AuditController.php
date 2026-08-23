<?php

namespace App\Http\Controllers;

use App\Models\ApprovalRequest;
use App\Models\AuditEvent;

class AuditController extends Controller
{
    public function index()
    {
        $events = AuditEvent::with('user')->latest('id')->paginate(30);
        $approvals = ApprovalRequest::with('requester', 'decider')->latest('id')->limit(15)->get();

        return view('audit.index', compact('events', 'approvals'));
    }
}
