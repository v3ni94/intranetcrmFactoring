<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\CreditLine;
use App\Models\FactoringProduct;
use App\Models\JournalEntry;
use App\Models\Organization;
use App\Models\Purchase;
use App\Models\Receivable;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceivableWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private Organization $customer;

    private Contract $contract;

    private User $operations;

    private User $risk;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(DemoUserSeeder::class);

        $tenantId = Tenant::where('slug', 'aurevia-demo')->value('id');

        $this->operations = User::where('email', 'demo.operations@aurevia-factoring.de')->first();
        $this->risk = User::where('email', 'demo.kredit_risiko@aurevia-factoring.de')->first();

        $product = FactoringProduct::create([
            'tenant_id' => $tenantId, 'name' => 'Test-Produkt', 'recourse_type' => 'unecht_mit_regress',
            'service_type' => 'full_service', 'disclosure_type' => 'offen', 'scope_type' => 'gesamtumsatz', 'active' => true,
        ]);

        $this->customer = Organization::create([
            'tenant_id' => $tenantId, 'org_type' => 'customer', 'name' => 'Testpraxis', 'customer_status' => 'Aktiv', 'is_demo' => true,
        ]);

        $this->contract = Contract::create([
            'tenant_id' => $tenantId, 'organization_id' => $this->customer->id, 'factoring_product_id' => $product->id,
            'contract_number' => 'TEST-001', 'status' => 'aktiv', 'purchase_line' => 100000, 'payout_line' => 90000,
            'advance_rate_percent' => 85, 'reserve_percent' => 15, 'factoring_fee_percent' => 1.5,
            'reference_rate_percent' => 3, 'margin_percent' => 2.5, 'max_days_outstanding' => 120, 'is_demo' => true,
        ]);

        CreditLine::create([
            'tenant_id' => $tenantId, 'organization_id' => $this->customer->id, 'contract_id' => $this->contract->id,
            'line_type' => 'ankauf', 'limit_amount' => 100000, 'used_amount' => 0, 'status' => 'aktiv', 'is_demo' => true,
        ]);
        CreditLine::create([
            'tenant_id' => $tenantId, 'organization_id' => $this->customer->id, 'contract_id' => $this->contract->id,
            'line_type' => 'auszahlung', 'limit_amount' => 90000, 'used_amount' => 0, 'status' => 'aktiv', 'is_demo' => true,
        ]);
    }

    /**
     * Vollstaendiger Prozess: Einreichen -> Pruefen -> Freigeben -> Ankauf mit Vier-Augen-Prinzip.
     * Deckt Abnahmekriterien 3 (durchklickbar), 4 (Auszahlung geht auf Genehmigungen zurueck) und 10 (Vier-Augen) ab.
     */
    public function test_full_receivable_to_purchase_workflow_enforces_four_eyes_principle(): void
    {
        $receivable = Receivable::create([
            'tenant_id' => $this->customer->tenant_id,
            'receivable_number' => 'FRD-TEST-000001',
            'organization_id' => $this->customer->id,
            'contract_id' => $this->contract->id,
            'invoice_number' => 'RG-TEST-1',
            'invoice_date' => now()->subDays(5),
            'due_date' => now()->addDays(25),
            'invoice_amount' => 10000,
            'status' => 'eingereicht',
        ]);

        $this->actingAs($this->operations)
            ->post(route('receivables.formal-check', $receivable))
            ->assertRedirect();
        $receivable->refresh();
        $this->assertSame('formale_pruefung', $receivable->status);

        $this->actingAs($this->operations)
            ->post(route('receivables.risk-check', $receivable))
            ->assertRedirect();
        $receivable->refresh();
        $this->assertSame('freigegeben', $receivable->status);

        $this->actingAs($this->operations)
            ->post(route('purchases.calculate', $receivable))
            ->assertRedirect();

        $purchase = Purchase::where('receivable_id', $receivable->id)->firstOrFail();
        $this->assertSame('berechnet', $purchase->status);
        $this->assertSame($this->operations->id, $purchase->approved_by_first);

        // Nominal = Auszahlung + Reserve + Gebuehr + Zins (nachvollziehbare Formel, Abschnitt 11.1)
        $sum = round(
            (float) $purchase->immediate_payout_amount + (float) $purchase->reserve_amount
            + (float) $purchase->factoring_fee_amount + (float) $purchase->expected_interest_amount,
            2
        );
        $this->assertSame(round((float) $purchase->nominal_amount, 2), $sum);

        // Vier-Augen-Prinzip: dieselbe Person darf nicht zweitfreigeben.
        $this->actingAs($this->operations)
            ->post(route('purchases.approve-second', $purchase))
            ->assertForbidden();

        $this->actingAs($this->risk)
            ->post(route('purchases.approve-second', $purchase))
            ->assertRedirect();

        $purchase->refresh();
        $receivable->refresh();
        $this->assertSame('freigegeben', $purchase->status);
        $this->assertSame('angekauft', $receivable->status);
        $this->assertNotNull($purchase->approved_by_second);
        $this->assertNotSame($purchase->approved_by_first, $purchase->approved_by_second);

        // Journal ist ausgeglichen und auf den Ankauf zurueckfuehrbar.
        $entry = JournalEntry::where('source_type', Purchase::class)->where('source_id', $purchase->id)->firstOrFail();
        $this->assertTrue($entry->isBalanced());
    }

    public function test_customer_cannot_see_other_customers_receivable(): void
    {
        $tenantId = $this->customer->tenant_id;

        $otherCustomer = Organization::create([
            'tenant_id' => $tenantId, 'org_type' => 'customer', 'name' => 'Andere Praxis', 'customer_status' => 'Aktiv', 'is_demo' => true,
        ]);
        $receivable = Receivable::create([
            'tenant_id' => $tenantId, 'receivable_number' => 'FRD-TEST-000002', 'organization_id' => $otherCustomer->id,
            'contract_id' => $this->contract->id, 'invoice_number' => 'RG-TEST-2', 'invoice_date' => now(),
            'due_date' => now()->addDays(30), 'invoice_amount' => 500, 'status' => 'eingereicht',
        ]);

        $kunde = User::where('email', 'demo.kunde_admin@aurevia-factoring.de')->first();
        $kunde->update(['customer_org_id' => $this->customer->id]);

        $this->actingAs($kunde)->get(route('customer.receivables.show', $receivable))->assertForbidden();
    }
}
