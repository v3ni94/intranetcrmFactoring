<?php

namespace App\Http\Controllers;

use App\Models\DunningCase;
use App\Models\Receivable;
use App\Services\Integrations\CollectionsAdapter;
use App\Support\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Http\Request;

class DunningController extends Controller
{
    public function index()
    {
        $cases = DunningCase::with('receivable.organization', 'assignee')->latest('id')->paginate(20);
        $overdueReceivables = Receivable::where('status', 'ueberfaellig')->whereDoesntHave('dunningCases', fn ($q) => $q->whereIn('status', ['offen', 'in_klaerung']))->get();

        return view('dunning.index', compact('cases', 'overdueReceivables'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'receivable_id' => 'required|exists:receivables,id',
            'case_type' => 'required|in:mahnung,streitfall,rueckgriff,ausfall',
            'reason' => 'nullable|string|max:500',
        ]);

        $receivable = Receivable::findOrFail($data['receivable_id']);

        $case = DunningCase::create([
            'tenant_id' => TenantContext::id(),
            'receivable_id' => $receivable->id,
            'case_type' => $data['case_type'],
            'dunning_level' => 1,
            'reason' => $data['reason'] ?? null,
            'open_amount' => $receivable->invoice_amount,
            'assignee_id' => $request->user()->id,
            'next_action_date' => now()->addDays(7),
        ]);

        if ($data['case_type'] === 'streitfall') {
            $receivable->update(['status' => 'streitig']);
        } elseif ($data['case_type'] === 'rueckgriff') {
            $receivable->update(['status' => 'rueckgriff']);
        } elseif ($data['case_type'] === 'ausfall') {
            $receivable->update(['status' => 'ausgefallen']);
        }

        AuditLogger::log('create', DunningCase::class, $case->id, [], $case->toArray());

        return back()->with('status', 'Fall angelegt: '.$data['case_type']);
    }

    public function close(Request $request, DunningCase $case)
    {
        $case->update(['status' => 'geschlossen']);
        AuditLogger::log('update', DunningCase::class, $case->id, [], ['status' => 'geschlossen']);

        return back()->with('status', 'Fall geschlossen.');
    }

    public function handOverToCollections(DunningCase $case, CollectionsAdapter $adapter)
    {
        $reference = $adapter->handOver($case);
        AuditLogger::log('update', DunningCase::class, $case->id, [], ['status' => 'inkasso'], 'Übergabe an Inkasso-Partner (Demo)');

        return back()->with('status', "An Inkasso-Partner übergeben (Demo), Referenz {$reference}.");
    }
}
