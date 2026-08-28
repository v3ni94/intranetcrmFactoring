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

    /**
     * Eskalationsprozess nach Markt/Marktfolge-Prinzip (MaRisk, v3.00):
     * Gegen eine Ablehnung (z.B. Bonitaet/Regelpruefung) kann der Markt ein
     * Zweitvotum der Marktfolge anfordern. Lehnt die Marktfolge ab, eskaliert
     * der Fall an den Vorstand als letzte Instanz. Jede Entscheidung erfordert
     * eine Begruendung und wird revisionssicher auditiert. Der Aufsichtsrat
     * entscheidet bewusst NICHT operativ (Ueberwachungsorgan), er erhaelt die
     * Eskalationen aggregiert im Reporting.
     */
    public function requestSecondVote(Request $request, Receivable $receivable)
    {
        abort_unless(in_array($receivable->status, ['abgelehnt', 'rueckfrage'], true), 422,
            'Ein Zweitvotum ist nur bei abgelehnten Forderungen oder Rückfragen möglich.');

        $data = $request->validate(['reason' => 'required|string|max:500']);

        $receivable->update(['status' => 'zweitvotum_marktfolge']);
        AuditLogger::log('escalate', Receivable::class, $receivable->id,
            [], ['status' => 'zweitvotum_marktfolge'], 'Zweitvotum Marktfolge angefordert: '.$data['reason']);

        return back()->with('status', 'Zweitvotum der Marktfolge angefordert.');
    }

    public function marketFollowUpVote(Request $request, Receivable $receivable)
    {
        abort_unless($request->user()->hasAnyRole(['kredit_risiko', 'geschaeftsleitung', 'superadmin_demo']), 403);
        abort_unless($receivable->status === 'zweitvotum_marktfolge', 422, 'Kein offenes Marktfolge-Votum.');

        $data = $request->validate([
            'decision' => 'required|in:freigeben,ablehnen',
            'reason' => 'required|string|max:500',
        ]);

        if ($data['decision'] === 'freigeben') {
            $receivable->update(['status' => 'freigegeben', 'reviewed_by' => $request->user()->id, 'rejection_reason' => null]);
            AuditLogger::log('approve', Receivable::class, $receivable->id, [], ['status' => 'freigegeben'],
                'Marktfolge-Zweitvotum: freigegeben — '.$data['reason']);

            return back()->with('status', 'Marktfolge hat freigegeben. Ankauf kann berechnet werden (Vier-Augen-Prinzip gilt weiterhin).');
        }

        $receivable->update(['status' => 'zweitvotum_vorstand', 'rejection_reason' => $data['reason']]);
        AuditLogger::log('escalate', Receivable::class, $receivable->id, [], ['status' => 'zweitvotum_vorstand'],
            'Marktfolge-Zweitvotum: abgelehnt, Eskalation an Vorstand — '.$data['reason']);

        return back()->with('status', 'Marktfolge hat abgelehnt. Der Fall liegt jetzt beim Vorstand.');
    }

    public function boardVote(Request $request, Receivable $receivable)
    {
        abort_unless($request->user()->hasAnyRole(['geschaeftsleitung', 'superadmin_demo']), 403);
        abort_unless($receivable->status === 'zweitvotum_vorstand', 422, 'Kein offenes Vorstands-Votum.');

        $data = $request->validate([
            'decision' => 'required|in:freigeben,ablehnen',
            'reason' => 'required|string|max:500',
        ]);

        if ($data['decision'] === 'freigeben') {
            $receivable->update(['status' => 'freigegeben', 'reviewed_by' => $request->user()->id, 'rejection_reason' => null]);
            AuditLogger::log('approve', Receivable::class, $receivable->id, [], ['status' => 'freigegeben'],
                'Vorstands-Votum: freigegeben — '.$data['reason']);

            return back()->with('status', 'Vorstand hat freigegeben.');
        }

        $receivable->update(['status' => 'abgelehnt', 'rejection_reason' => $data['reason'], 'reviewed_by' => $request->user()->id]);
        AuditLogger::log('reject', Receivable::class, $receivable->id, [], ['status' => 'abgelehnt'],
            'Vorstands-Votum: endgültig abgelehnt — '.$data['reason']);

        return back()->with('status', 'Vorstand hat endgültig abgelehnt.');
    }

    public function reject(Request $request, Receivable $receivable)
    {
        // Nur Forderungen im Pruefprozess sind ablehnbar. Angekaufte, ausgezahlte
        // oder abgerechnete Forderungen wuerden sonst still aus Buchhaltung und
        // Limiten "verschwinden" (illegale Statustransition).
        abort_unless(
            in_array($receivable->status, ['eingereicht', 'formale_pruefung', 'risiko_limitpruefung', 'rueckfrage', 'freigegeben'], true),
            422,
            'Nur Forderungen im Prüfprozess können abgelehnt werden.'
        );

        $data = $request->validate(['reason' => 'required|string|max:500']);
        $receivable->update(['status' => 'abgelehnt', 'rejection_reason' => $data['reason'], 'reviewed_by' => $request->user()->id]);

        AuditLogger::log('reject', Receivable::class, $receivable->id, [], [], $data['reason']);

        return back()->with('status', 'Forderung abgelehnt.');
    }
}
