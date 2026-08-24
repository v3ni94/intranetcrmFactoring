<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\PayoutBatch;
use App\Models\Purchase;
use App\Services\Integrations\BankFileAdapter;
use App\Services\JournalService;
use App\Services\SepaExportService;
use App\Support\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PayoutBatchController extends Controller
{
    public function index()
    {
        $batches = PayoutBatch::with('bankAccount', 'payouts')->latest('id')->paginate(20);
        $readyPurchases = Purchase::where('status', 'freigegeben')->whereDoesntHave('payout')->with('receivable.organization')->get();
        $bankAccounts = BankAccount::where('purpose', 'auszahlung')->get();

        return view('payouts.index', compact('batches', 'readyPurchases', 'bankAccounts'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'purchase_ids' => 'required|array|min:1',
            'purchase_ids.*' => 'exists:purchases,id',
        ]);

        $purchases = Purchase::whereIn('id', $data['purchase_ids'])->where('status', 'freigegeben')->whereDoesntHave('payout')->get();
        abort_if($purchases->isEmpty(), 422, 'Keine gültigen Ankäufe für Auszahlung ausgewählt.');

        $batch = PayoutBatch::create([
            'tenant_id' => TenantContext::id(),
            'batch_number' => 'BATCH-'.now()->format('ymd').'-'.strtoupper(Str::random(4)),
            'bank_account_id' => $data['bank_account_id'],
            'total_amount' => $purchases->sum('immediate_payout_amount'),
            'item_count' => $purchases->count(),
            'status' => 'erstellt',
        ]);

        foreach ($purchases as $purchase) {
            $batch->payouts()->create([
                'tenant_id' => TenantContext::id(),
                'purchase_id' => $purchase->id,
                'organization_id' => $purchase->receivable->organization_id,
                'amount' => $purchase->immediate_payout_amount,
                'idempotency_key' => 'PAYOUT-'.$purchase->id.'-'.Str::random(8),
                'status' => 'erstellt',
            ]);

            $purchase->receivable->update(['status' => 'zur_auszahlung']);
        }

        AuditLogger::log('create', PayoutBatch::class, $batch->id, [], $batch->toArray());

        return redirect()->route('payouts.index')->with('status', "Auszahlungsbatch {$batch->batch_number} erstellt. Erste Freigabe erforderlich.");
    }

    public function approveFirst(Request $request, PayoutBatch $batch)
    {
        abort_unless($batch->status === 'erstellt', 422, 'Batch ist bereits in Freigabe oder abgeschlossen.');
        $batch->update(['status' => 'freigegeben_1', 'approved_by_first' => $request->user()->id]);
        AuditLogger::log('approve', PayoutBatch::class, $batch->id, [], ['status' => $batch->status]);

        return back()->with('status', 'Erste Freigabe erteilt.');
    }

    public function approveSecond(Request $request, PayoutBatch $batch, SepaExportService $sepa, BankFileAdapter $bankAdapter)
    {
        $reference = DB::transaction(function () use ($request, $batch, $sepa) {
            // Zeilensperre + erneute Statuspruefung gegen parallele Doppelfreigabe.
            $batch = PayoutBatch::whereKey($batch->id)->lockForUpdate()->firstOrFail();

            abort_unless($batch->status === 'freigegeben_1', 422, 'Batch benötigt zunächst eine erste Freigabe.');
            abort_if($batch->approved_by_first === $request->user()->id, 403, 'Vier-Augen-Prinzip: Zweitfreigabe durch eine andere Person erforderlich.');

            $reference = $sepa->exportPain001($batch);

            $batch->update([
                'status' => 'angewiesen',
                'approved_by_second' => $request->user()->id,
                'sepa_export_reference' => $reference,
                'executed_at' => now(),
            ]);

            $batch->payouts()->update(['status' => 'angewiesen']);
            $batch->load('payouts.purchase.receivable');
            $batch->payouts->each(fn ($p) => $p->purchase->receivable->update(['status' => 'zahlung_angewiesen']));

            AuditLogger::log('approve', PayoutBatch::class, $batch->id, [], ['status' => $batch->status, 'sepa' => $reference]);

            return $reference;
        });

        $bankAdapter->logSepaExport($batch->refresh(), $reference);

        return back()->with('status', "Zweite Freigabe erteilt. SEPA-Demo-Datei erzeugt: {$reference}");
    }

    public function confirm(Request $request, PayoutBatch $batch, JournalService $journal)
    {
        abort_unless($batch->status === 'angewiesen', 422, 'Batch ist noch nicht angewiesen.');

        // Transaktional: Zahlungsbestaetigung, Statuswechsel und Journalbuchungen
        // werden nur gemeinsam wirksam. Eager Loading vermeidet N+1 je Payout.
        DB::transaction(function () use ($request, $batch, $journal) {
            $batch->load('payouts.purchase.receivable');

            $batch->payouts->each(function ($payout) use ($journal, $request) {
                $payout->update(['status' => 'bestaetigt', 'confirmed_at' => now()]);
                $payout->purchase->receivable->update(['status' => 'ausgezahlt']);

                $journal->post('auszahlung', [
                    ['account' => '2100', 'debit' => (float) $payout->amount, 'organization_id' => $payout->organization_id],
                    ['account' => '1200', 'credit' => (float) $payout->amount, 'organization_id' => $payout->organization_id],
                ], get_class($payout), $payout->id, $request->user()->id);
            });

            $batch->bankAccount->decrement('balance_amount', $batch->total_amount);
            $batch->update(['status' => 'bestaetigt']);

            AuditLogger::log('update', PayoutBatch::class, $batch->id, [], ['status' => 'bestaetigt'], 'Bankbestätigung (Demo) erhalten');
        });

        return back()->with('status', 'Auszahlung bestätigt (Demo-Banking).');
    }
}
