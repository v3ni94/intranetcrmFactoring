<?php

namespace App\Http\Controllers;

use App\Models\CreditLine;
use App\Models\DebtorLimit;
use App\Models\Purchase;
use App\Models\Receivable;
use App\Services\JournalService;
use App\Services\PurchaseCalculator;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    public function calculate(Request $request, Receivable $receivable, PurchaseCalculator $calculator)
    {
        abort_unless($receivable->status === 'freigegeben', 422, 'Ankauf kann nur für freigegebene Forderungen berechnet werden.');
        abort_if($receivable->purchase, 422, 'Für diese Forderung liegt bereits eine Ankaufsberechnung vor.');

        $purchase = $calculator->calculate($receivable);
        $purchase->update(['approved_by_first' => $request->user()->id]);

        AuditLogger::log('create', Purchase::class, $purchase->id, [], $purchase->toArray());

        return back()->with('status', 'Ankauf berechnet. Zweite Freigabe (Vier-Augen-Prinzip) erforderlich.');
    }

    public function approveSecond(Request $request, Purchase $purchase, JournalService $journal)
    {
        // Transaktional mit Zeilensperre: verhindert Doppelfreigabe bei parallelen
        // Requests und stellt sicher, dass Statuswechsel, Limitfortschreibung und
        // Journalbuchung nur gemeinsam wirksam werden (alles oder nichts).
        DB::transaction(function () use ($request, $purchase, $journal) {
            $purchase = Purchase::whereKey($purchase->id)->lockForUpdate()->firstOrFail();

            abort_unless($purchase->status === 'berechnet', 422, 'Ankauf ist bereits final.');
            abort_if($purchase->approved_by_first === $request->user()->id, 403, 'Vier-Augen-Prinzip: Zweitfreigabe durch eine andere Person erforderlich.');

            $receivable = $purchase->receivable;
            $contract = $receivable->contract;

            $purchase->update([
                'status' => 'freigegeben',
                'approved_by_second' => $request->user()->id,
                'purchased_at' => now(),
            ]);

            $receivable->update(['status' => 'angekauft']);

            // Linien und Limits fortschreiben
            $purchaseLine = CreditLine::where('organization_id', $receivable->organization_id)->where('line_type', 'ankauf')->where('status', 'aktiv')->first();
            $purchaseLine?->increment('used_amount', $purchase->nominal_amount);

            $payoutLine = CreditLine::where('organization_id', $receivable->organization_id)->where('line_type', 'auszahlung')->where('status', 'aktiv')->first();
            $payoutLine?->increment('used_amount', $purchase->immediate_payout_amount);

            if ($receivable->debtor_organization_id) {
                DebtorLimit::where('debtor_organization_id', $receivable->debtor_organization_id)
                    ->where('status', 'aktiv')->first()?->increment('used_amount', $purchase->nominal_amount);
            }

            $journal->post('ankauf', [
                ['account' => '1400', 'debit' => (float) $purchase->nominal_amount, 'organization_id' => $receivable->organization_id, 'contract_id' => $contract->id],
                ['account' => '2100', 'credit' => (float) $purchase->immediate_payout_amount, 'organization_id' => $receivable->organization_id],
                ['account' => '2000', 'credit' => (float) $purchase->reserve_amount, 'organization_id' => $receivable->organization_id],
                ['account' => '4000', 'credit' => round((float) $purchase->factoring_fee_amount + (float) $purchase->expected_interest_amount, 2), 'organization_id' => $receivable->organization_id],
            ], Purchase::class, $purchase->id, $request->user()->id);

            AuditLogger::log('approve', Purchase::class, $purchase->id, [], ['status' => 'freigegeben']);
        });

        return back()->with('status', 'Ankauf final freigegeben und verbucht.');
    }
}
