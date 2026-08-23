<?php

namespace App\Http\Controllers;

use App\Models\Receivable;
use App\Services\ReceivableRuleEngine;
use App\Support\AuditLogger;
use Illuminate\Http\Request;

class ReceivableController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->get('status');
        $query = Receivable::with('organization', 'contract')->latest('id');

        if ($status) {
            $query->where('status', $status);
        }

        $receivables = $query->paginate(25)->withQueryString();
        $statusCounts = Receivable::selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');

        return view('receivables.index', compact('receivables', 'statusCounts', 'status'));
    }

    public function show(Receivable $receivable)
    {
        $receivable->load('organization', 'contract', 'debtorOrganization', 'purchase', 'payments', 'dunningCases');

        return view('receivables.show', compact('receivable'));
    }

    public function formalCheck(Request $request, Receivable $receivable)
    {
        abort_unless($receivable->status === 'eingereicht', 422, 'Formale Prüfung nur aus Status "Eingereicht" möglich.');

        $missing = collect([
            'Rechnungsnummer' => filled($receivable->invoice_number),
            'Rechnungsdatum' => filled($receivable->invoice_date),
            'Fälligkeitsdatum' => filled($receivable->due_date),
            'Rechnungsbetrag' => (float) $receivable->invoice_amount > 0,
        ])->filter(fn ($ok) => ! $ok)->keys();

        if ($missing->isNotEmpty()) {
            $receivable->update(['status' => 'rueckfrage', 'rejection_reason' => 'Fehlende Angaben: '.$missing->implode(', ')]);
        } else {
            $receivable->update(['status' => 'formale_pruefung', 'reviewed_by' => $request->user()->id]);
        }

        AuditLogger::log('update', Receivable::class, $receivable->id, [], ['status' => $receivable->status], 'Formale Prüfung');

        return back()->with('status', 'Formale Prüfung abgeschlossen: '.$receivable->statusLabel());
    }

    public function riskCheck(Request $request, Receivable $receivable, ReceivableRuleEngine $engine)
    {
        abort_unless(in_array($receivable->status, ['formale_pruefung', 'rueckfrage']), 422, 'Risiko-/Limitprüfung in diesem Status nicht möglich.');

        $receivable->update(['status' => 'risiko_limitpruefung']);
        $result = $engine->evaluate($receivable);

        if ($result['passed']) {
            $receivable->update(['status' => 'freigegeben', 'reviewed_by' => $request->user()->id, 'rejection_reason' => null, 'triggered_rule' => null]);
            $message = 'Freigegeben – Regelprüfung ohne Beanstandung.';
        } else {
            $receivable->update([
                'status' => 'rueckfrage',
                'rejection_reason' => $result['reason'],
                'triggered_rule' => $result['rule'],
                'reviewed_by' => $request->user()->id,
            ]);
            $message = 'Rückfrage ausgelöst: '.$result['reason'];
        }

        AuditLogger::log('update', Receivable::class, $receivable->id, [], ['status' => $receivable->status], $message);

        return back()->with('status', $message);
    }

    public function reject(Request $request, Receivable $receivable)
    {
        $data = $request->validate(['reason' => 'required|string|max:500']);
        $receivable->update(['status' => 'abgelehnt', 'rejection_reason' => $data['reason'], 'reviewed_by' => $request->user()->id]);

        AuditLogger::log('reject', Receivable::class, $receivable->id, [], [], $data['reason']);

        return back()->with('status', 'Forderung abgelehnt.');
    }
}
