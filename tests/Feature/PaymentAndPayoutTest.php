<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Contract;
use App\Models\CreditLine;
use App\Models\FactoringProduct;
use App\Models\Organization;
use App\Models\PayoutBatch;
use App\Models\Purchase;
use App\Models\Receivable;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Zahlungszuordnung, Teilzahlungen, Schlussabrechnung und Vier-Augen-Prinzip
 * bei Auszahlungsbatches (Abschnitte 11.2/11.3, Abnahmekriterium 10).
 */
class PaymentAndPayoutTest extends TestCase
{
    use RefreshDatabase;

    private int $tenantId;

    private Organization $customer;

    private Contract $contract;

    private User $operations;

    private User $risk;

    private BankAccount $account;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(DemoUserSeeder::class);

        $this->tenantId = Tenant::where('slug', 'aurevia-demo')->value('id');
        $this->operations = User::where('email', 'demo.operations@aurevia-factoring.de')->first();
        $this->risk = User::where('email', 'demo.kredit_risiko@aurevia-factoring.de')->first();

        $product = FactoringProduct::create([
            'tenant_id' => $this->tenantId, 'name' => 'Test-Produkt', 'recourse_type' => 'unecht_mit_regress',
            'service_type' => 'full_service', 'disclosure_type' => 'offen', 'scope_type' => 'gesamtumsatz', 'active' => true,
        ]);
        $this->customer = Organization::create([
            'tenant_id' => $this->tenantId, 'org_type' => 'customer', 'name' => 'Testpraxis', 'customer_status' => 'Aktiv', 'is_demo' => true,
        ]);
        $this->contract = Contract::create([
            'tenant_id' => $this->tenantId, 'organization_id' => $this->customer->id, 'factoring_product_id' => $product->id,
            'contract_number' => 'TEST-001', 'status' => 'aktiv', 'purchase_line' => 100000, 'payout_line' => 90000,
            'advance_rate_percent' => 85, 'reserve_percent' => 15, 'factoring_fee_percent' => 1.5,
            'reference_rate_percent' => 3, 'margin_percent' => 2.5, 'max_days_outstanding' => 120, 'is_demo' => true,
        ]);
        CreditLine::create([
            'tenant_id' => $this->tenantId, 'organization_id' => $this->customer->id, 'contract_id' => $this->contract->id,
            'line_type' => 'ankauf', 'limit_amount' => 100000, 'used_amount' => 0, 'status' => 'aktiv', 'is_demo' => true,
        ]);
        CreditLine::create([
            'tenant_id' => $this->tenantId, 'organization_id' => $this->customer->id, 'contract_id' => $this->contract->id,
            'line_type' => 'auszahlung', 'limit_amount' => 90000, 'used_amount' => 0, 'status' => 'aktiv', 'is_demo' => true,
        ]);

        $this->account = BankAccount::create([
            'tenant_id' => $this->tenantId, 'account_name' => 'Auszahlungskonto', 'bank_name' => 'Medizinbank AG - Demo',
            'iban_masked' => 'DE00 **** 0001', 'purpose' => 'auszahlung', 'balance_amount' => 500000, 'is_demo' => true,
        ]);
    }

    private function makeReceivable(string $status, float $amount = 10000, string $suffix = '1'): Receivable
    {
        return Receivable::create([
            'tenant_id' => $this->tenantId,
            'receivable_number' => 'FRD-TEST-PAY-'.$suffix,
            'organization_id' => $this->customer->id,
            'contract_id' => $this->contract->id,
            'invoice_number' => 'RG-PAY-'.$suffix,
            'invoice_date' => now()->subDays(10),
            'due_date' => now()->addDays(20),
            'invoice_amount' => $amount,
            'status' => $status,
        ]);
    }

    private function makeTransaction(float $amount): BankTransaction
    {
        return BankTransaction::create([
            'tenant_id' => $this->tenantId, 'bank_account_id' => $this->account->id,
            'value_date' => now()->toDateString(), 'amount' => $amount,
            'reference' => 'Testzahlung', 'status' => 'offen', 'is_demo' => true,
        ]);
    }

    public function test_payout_batch_enforces_four_eyes_principle(): void
    {
        $receivable = $this->makeReceivable('angekauft');
        $purchase = Purchase::create([
            'tenant_id' => $this->tenantId, 'receivable_id' => $receivable->id,
            'nominal_amount' => 10000, 'purchasable_amount' => 10000, 'advance_rate_percent' => 85,
            'immediate_payout_amount' => 8000, 'reserve_amount' => 1500, 'factoring_fee_amount' => 150,
            'expected_interest_amount' => 350, 'deductions_amount' => 0, 'status' => 'freigegeben',
            'approved_by_first' => $this->operations->id, 'approved_by_second' => $this->risk->id,
        ]);

        $this->actingAs($this->operations)->post(route('payouts.store'), [
            'bank_account_id' => $this->account->id,
            'purchase_ids' => [$purchase->id],
        ])->assertRedirect();

        $batch = PayoutBatch::firstOrFail();

        $this->actingAs($this->operations)->post(route('payouts.approve-first', $batch))->assertRedirect();
        $batch->refresh();
        $this->assertSame('freigegeben_1', $batch->status);

        // Vier-Augen-Prinzip: Erstfreigeber darf nicht zweitfreigeben.
        $this->actingAs($this->operations)->post(route('payouts.approve-second', $batch))->assertForbidden();
        $this->assertSame('freigegeben_1', $batch->fresh()->status);

        $this->actingAs($this->risk)->post(route('payouts.approve-second', $batch))->assertRedirect();
        $batch->refresh();
        $this->assertSame('angewiesen', $batch->status);
        $this->assertSame('zahlung_angewiesen', $receivable->fresh()->status);
    }

    public function test_partial_payments_accumulate_until_receivable_is_paid_and_settled(): void
    {
        $receivable = $this->makeReceivable('ausgezahlt', 10000);
        Purchase::create([
            'tenant_id' => $this->tenantId, 'receivable_id' => $receivable->id,
            'nominal_amount' => 10000, 'purchasable_amount' => 10000, 'advance_rate_percent' => 85,
            'immediate_payout_amount' => 8000, 'reserve_amount' => 1500, 'factoring_fee_amount' => 150,
            'expected_interest_amount' => 350, 'deductions_amount' => 0, 'status' => 'freigegeben',
        ]);

        // Abrechnung vor vollstaendiger Zahlung ist gesperrt.
        $this->actingAs($this->operations)->post(route('payments.settle', $receivable))->assertStatus(422);

        // Erste Haelfte: teilbezahlt.
        $t1 = $this->makeTransaction(5000);
        $this->actingAs($this->operations)->post(route('payments.match', $t1), ['receivable_id' => $receivable->id])->assertRedirect();
        $this->assertSame('teilbezahlt', $receivable->fresh()->status);

        // Zweite Haelfte: kumuliert vollstaendig bezahlt.
        $t2 = $this->makeTransaction(5000);
        $this->actingAs($this->operations)->post(route('payments.match', $t2), ['receivable_id' => $receivable->id])->assertRedirect();
        $this->assertSame('bezahlt', $receivable->fresh()->status);

        // Jetzt ist die Schlussabrechnung moeglich (Reservefreigabe).
        $this->actingAs($this->operations)->post(route('payments.settle', $receivable))->assertRedirect();
        $this->assertSame('abgerechnet', $receivable->fresh()->status);
    }

    public function test_bank_transaction_cannot_be_matched_twice(): void
    {
        $receivable = $this->makeReceivable('ausgezahlt', 10000, '2');
        $transaction = $this->makeTransaction(10000);

        $this->actingAs($this->operations)->post(route('payments.match', $transaction), ['receivable_id' => $receivable->id])->assertRedirect();
        $this->assertSame('zugeordnet', $transaction->fresh()->status);
        $this->assertSame(1, $receivable->payments()->count());

        // Zweiter Versuch (Doppelklick/Replay) wird abgelehnt und bucht nichts erneut.
        $this->actingAs($this->operations)->post(route('payments.match', $transaction), ['receivable_id' => $receivable->id])->assertStatus(422);
        $this->assertSame(1, $receivable->payments()->count());
    }

    public function test_payment_cannot_be_matched_to_closed_receivable(): void
    {
        $receivable = $this->makeReceivable('abgerechnet', 10000, '3');
        $transaction = $this->makeTransaction(10000);

        $this->actingAs($this->operations)->post(route('payments.match', $transaction), ['receivable_id' => $receivable->id])->assertStatus(422);
        $this->assertSame('offen', $transaction->fresh()->status);
    }
}
