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

class PaymentController extends Controller
{
    public function index(PaymentMatcher $matcher)
    {
        $openTransactions = BankTransaction::where('status', 'offen')->where('amount', '>', 0)->get()
            ->map(function (BankTransaction $t) use ($matcher) {
                $t->suggestion = $matcher->suggest($t);

                return $t;
            });

        $matched = BankTransaction::where('status', 'zugeordnet')->with('payments.receivable')->latest('id')->limit(20)->get();

        return view('payments.index', compact('openTransactions', 'matched'));
    }

    public function importDemo(Request $request, PaymentMatcher $matcher, BankFileAdapter $bankAdapter)
    {
        $account = BankAccount::where('purpose', 'betrieb')->first() ?? BankAccount::first();
        abort_unless($account, 422, 'Kein Bankkonto vorhanden.');

        $receivables = Receivable::whereIn('status', ['zahlung_angewiesen', 'ausgezahlt'])->inRandomOrder()->limit(3)->get();

        if ($receivables->isEmpty()) {
            return back()->with('status', 'Keine passenden offenen Forderungen für einen Demo-Kontoauszug gefunden.');
        }

        $created = $matcher->generateDemoStatement($account->id, $receivables);
        AuditLogger::log('import', BankTransaction::class, null, [], ['count' => count($created)], 'camt.053 Demo-Import');
        $bankAdapter->logStatementImport(count($created));

        return back()->with('status', count($created).' Kontobewegung(en) importiert (camt.053-Demo).');
    }

    public function match(Request $request, BankTransaction $transaction, JournalService $journal)
    {
        $data = $request->validate(['receivable_id' => 'required|exists:receivables,id']);
        $receivable = Receivable::findOrFail($data['receivable_id']);

        $payment = $transaction->payments()->create([
            'tenant_id' => TenantContext::id(),
            'receivable_id' => $receivable->id,
            'amount' => $transaction->amount,
            'type' => (float) $transaction->amount >= (float) $receivable->invoice_amount ? 'eingang' : 'teilzahlung',
            'match_confidence_percent' => 100,
            'match_reason' => 'Manuell bestätigt durch '.$request->user()->name,
            'matched_by' => $request->user()->id,
            'matched_at' => now(),
        ]);

        $transaction->update(['status' => 'zugeordnet']);
        $transaction->bankAccount->increment('balance_amount', $transaction->amount);

        $newStatus = (float) $transaction->amount >= (float) $receivable->invoice_amount ? 'bezahlt' : 'teilbezahlt';
        $receivable->update(['status' => $newStatus]);

        $journal->post('zahlungseingang', [
            ['account' => '1200', 'debit' => (float) $transaction->amount, 'organization_id' => $receivable->organization_id],
            ['account' => '1400', 'credit' => (float) $transaction->amount, 'organization_id' => $receivable->organization_id],
        ], Receivable::class, $receivable->id, $request->user()->id);

        AuditLogger::log('update', Receivable::class, $receivable->id, [], ['status' => $newStatus], 'Zahlungszuordnung');

        return back()->with('status', 'Zahlung zugeordnet und verbucht.');
    }

    public function settle(Request $request, Receivable $receivable, JournalService $journal)
    {
        abort_unless($receivable->status === 'bezahlt', 422, 'Abrechnung nur bei vollständig bezahlten Forderungen möglich.');
        $purchase = $receivable->purchase;

        if ($purchase && (float) $purchase->reserve_amount > 0) {
            $journal->post('reservefreigabe', [
                ['account' => '2000', 'debit' => (float) $purchase->reserve_amount, 'organization_id' => $receivable->organization_id],
                ['account' => '2100', 'credit' => (float) $purchase->reserve_amount, 'organization_id' => $receivable->organization_id],
            ], Receivable::class, $receivable->id, $request->user()->id);
        }

        $receivable->update(['status' => 'abgerechnet']);
        AuditLogger::log('update', Receivable::class, $receivable->id, [], ['status' => 'abgerechnet'], 'Schlussabrechnung, Reservefreigabe');

        return back()->with('status', 'Forderung abgerechnet, Sicherheitseinbehalt freigegeben.');
    }
}
