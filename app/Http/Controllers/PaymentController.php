<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Receivable;
use App\Services\Integrations\BankFileAdapter;
use App\Services\JournalService;
use App\Services\PaymentMatcher;
use App\Support\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function index(PaymentMatcher $matcher)
    {
        $candidates = $matcher->openReceivables();
        $openTransactions = BankTransaction::where('status', 'offen')->where('amount', '>', 0)->get()
            ->map(function (BankTransaction $t) use ($matcher, $candidates) {
                $t->suggestion = $matcher->suggest($t, $candidates);

                return $t;
            });

        $matched = BankTransaction::where('status', 'zugeordnet')->with('payments.receivable')->latest('id')->limit(20)->get();

        return view('payments.index', compact('openTransactions', 'matched'));
    }

    public function importDemo(Request $request, PaymentMatcher $matcher, BankFileAdapter $bankAdapter)
    {
        $account = BankAccount::where('purpose', 'betrieb')->first() ?? BankAccount::first();
        abort_unless($account, 422, __('Kein Bankkonto vorhanden.'));

        $receivables = Receivable::whereIn('status', ['zahlung_angewiesen', 'ausgezahlt'])->inRandomOrder()->limit(3)->get();

        if ($receivables->isEmpty()) {
            return back()->with('status', __('Keine passenden offenen Forderungen für einen Demo-Kontoauszug gefunden.'));
        }

        $created = $matcher->generateDemoStatement($account->id, $receivables);
        AuditLogger::log('import', BankTransaction::class, null, [], ['count' => count($created)], 'camt.053 Demo-Import');
        $bankAdapter->logStatementImport(count($created));

        return back()->with('status', __(':count Kontobewegung(en) importiert (camt.053-Demo).', ['count' => count($created)]));
    }

    public function match(Request $request, BankTransaction $transaction, JournalService $journal)
    {
        $data = $request->validate(['receivable_id' => 'required|exists:receivables,id']);
        $receivable = Receivable::findOrFail($data['receivable_id']);

        abort_unless(
            in_array($receivable->status, PaymentMatcher::OPEN_RECEIVABLE_STATUSES, true),
            422,
            __('Zahlungen können nur offenen Forderungen zugeordnet werden.')
        );

        DB::transaction(function () use ($request, $transaction, $receivable, $journal) {
            // Zeilensperre + erneute Statuspruefung: verhindert Doppelzuordnung
            // derselben Banktransaktion (Doppelklick, parallele Requests).
            $transaction = BankTransaction::whereKey($transaction->id)->lockForUpdate()->firstOrFail();
            abort_unless($transaction->status === 'offen', 422, __('Diese Banktransaktion ist bereits zugeordnet.'));

            // Status anhand der KUMULIERTEN Zahlungen bestimmen, nicht nur der
            // aktuellen Transaktion — sonst wird eine in Raten bezahlte Forderung
            // nie 'bezahlt' und die Reservefreigabe (settle) bleibt blockiert.
            $paid = (float) $receivable->payments()->sum('amount') + (float) $transaction->amount;
            $fullyPaid = $paid >= (float) $receivable->invoice_amount;

            $transaction->payments()->create([
                'tenant_id' => TenantContext::id(),
                'receivable_id' => $receivable->id,
                'amount' => $transaction->amount,
                'type' => $fullyPaid ? 'eingang' : 'teilzahlung',
                'match_confidence_percent' => 100,
                'match_reason' => 'Manuell bestätigt durch '.$request->user()->name,
                'matched_by' => $request->user()->id,
                'matched_at' => now(),
            ]);

            $transaction->update(['status' => 'zugeordnet']);
            $transaction->bankAccount->increment('balance_amount', $transaction->amount);

            $newStatus = $fullyPaid ? 'bezahlt' : 'teilbezahlt';
            $receivable->update(['status' => $newStatus]);

            $journal->post('zahlungseingang', [
                ['account' => '1200', 'debit' => (float) $transaction->amount, 'organization_id' => $receivable->organization_id],
                ['account' => '1400', 'credit' => (float) $transaction->amount, 'organization_id' => $receivable->organization_id],
            ], Receivable::class, $receivable->id, $request->user()->id);

            $note = 'Zahlungszuordnung';
            if ($paid > (float) $receivable->invoice_amount) {
                $note .= sprintf(' — Überzahlung: kumuliert %.2f bei Rechnungsbetrag %.2f', $paid, (float) $receivable->invoice_amount);
            }
            AuditLogger::log('update', Receivable::class, $receivable->id, [], ['status' => $newStatus], $note);
        });

        return back()->with('status', __('Zahlung zugeordnet und verbucht.'));
    }

    public function settle(Request $request, Receivable $receivable, JournalService $journal)
    {
        abort_unless($receivable->status === 'bezahlt', 422, __('Abrechnung nur bei vollständig bezahlten Forderungen möglich.'));
        $purchase = $receivable->purchase;

        if ($purchase && (float) $purchase->reserve_amount > 0) {
            $journal->post('reservefreigabe', [
                ['account' => '2000', 'debit' => (float) $purchase->reserve_amount, 'organization_id' => $receivable->organization_id],
                ['account' => '2100', 'credit' => (float) $purchase->reserve_amount, 'organization_id' => $receivable->organization_id],
            ], Receivable::class, $receivable->id, $request->user()->id);
        }

        $receivable->update(['status' => 'abgerechnet']);
        AuditLogger::log('update', Receivable::class, $receivable->id, [], ['status' => 'abgerechnet'], 'Schlussabrechnung, Reservefreigabe');

        return back()->with('status', __('Forderung abgerechnet, Sicherheitseinbehalt freigegeben.'));
    }
}
