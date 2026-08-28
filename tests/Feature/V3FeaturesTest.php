<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\Facility;
use App\Models\FactoringProduct;
use App\Models\Organization;
use App\Models\Purchase;
use App\Models\Receivable;
use App\Models\Tenant;
use App\Models\Ticket;
use App\Models\User;
use App\Support\RatingCatalog;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * v3.00: Rating mit Gebuehrenaufschlag, Markt/Marktfolge-Eskalation,
 * Ticketsystem, Fazilitaets-Kuendigung, Controlling-Zugriff.
 */
class V3FeaturesTest extends TestCase
{
    use RefreshDatabase;

    private int $tenantId;

    private Organization $customer;

    private Contract $contract;

    private User $operations;

    private User $risk;

    private User $gl;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(DemoUserSeeder::class);

        $this->tenantId = Tenant::where('slug', 'aurevia-demo')->value('id');
        $this->operations = User::where('email', 'demo.operations@aurevia-factoring.de')->first();
        $this->risk = User::where('email', 'demo.kredit_risiko@aurevia-factoring.de')->first();
        $this->gl = User::where('email', 'demo.geschaeftsleitung@aurevia-factoring.de')->first();

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
    }

    private function makeReceivable(string $status): Receivable
    {
        return Receivable::create([
            'tenant_id' => $this->tenantId, 'receivable_number' => 'FRD-V3-'.uniqid(),
            'organization_id' => $this->customer->id, 'contract_id' => $this->contract->id,
            'invoice_number' => 'RG-V3-'.uniqid(), 'invoice_date' => now()->subDays(5),
            'due_date' => now()->addDays(25), 'invoice_amount' => 10000, 'status' => $status,
        ]);
    }

    public function test_rating_can_be_set_and_increases_purchase_fee(): void
    {
        // Rating setzen: 45 Punkte => Stufe B, Aufschlag 1,0 %-Punkte
        $this->actingAs($this->risk)->post(route('organizations.update-rating', $this->customer), [
            'rating_points' => 45, 'segment' => 'zahnarzt', 'customer_type' => 'b2b',
        ])->assertRedirect();

        $this->customer->refresh();
        $this->assertSame('B', $this->customer->rating);
        $this->assertSame('zahnarzt', $this->customer->segment);
        $this->assertSame(1.0, RatingCatalog::feeSurchargePercent('B'));

        // Ankauf: Gebuehr = (1,5 + 1,0) % von 10.000 = 250 EUR
        $receivable = $this->makeReceivable('freigegeben');
        $this->actingAs($this->operations)->post(route('purchases.calculate', $receivable))->assertRedirect();

        $purchase = Purchase::where('receivable_id', $receivable->id)->firstOrFail();
        $this->assertSame(250.0, round((float) $purchase->factoring_fee_amount, 2));
    }

    public function test_escalation_marktfolge_then_vorstand(): void
    {
        $receivable = $this->makeReceivable('abgelehnt');

        // Markt fordert Zweitvotum an
        $this->actingAs($this->operations)->post(route('receivables.request-second-vote', $receivable), [
            'reason' => 'Bonitätsauskunft veraltet, Kunde hat neue Unterlagen eingereicht.',
        ])->assertRedirect();
        $this->assertSame('zweitvotum_marktfolge', $receivable->fresh()->status);

        // Operations (Markt) darf NICHT selbst voten
        $this->actingAs($this->operations)->post(route('receivables.market-followup-vote', $receivable), [
            'decision' => 'freigeben', 'reason' => 'x',
        ])->assertForbidden();

        // Marktfolge lehnt ab -> Eskalation an Vorstand
        $this->actingAs($this->risk)->post(route('receivables.market-followup-vote', $receivable), [
            'decision' => 'ablehnen', 'reason' => 'Bonität weiterhin unzureichend.',
        ])->assertRedirect();
        $this->assertSame('zweitvotum_vorstand', $receivable->fresh()->status);

        // Marktfolge darf beim Vorstands-Votum nicht entscheiden
        $this->actingAs($this->risk)->post(route('receivables.board-vote', $receivable), [
            'decision' => 'freigeben', 'reason' => 'x',
        ])->assertForbidden();

        // Vorstand gibt frei
        $this->actingAs($this->gl)->post(route('receivables.board-vote', $receivable), [
            'decision' => 'freigeben', 'reason' => 'Gesamtwürdigung: Engagement vertretbar, Limite reduziert.',
        ])->assertRedirect();
        $this->assertSame('freigegeben', $receivable->fresh()->status);
    }

    public function test_ticket_visibility_and_internal_notes(): void
    {
        $kunde = User::where('email', 'demo.kunde_admin@aurevia-factoring.de')->first();
        $kunde->update(['customer_org_id' => $this->customer->id]);

        // Kunde erstellt Ticket
        $this->actingAs($kunde)->post(route('tickets.store'), [
            'subject' => 'Frage zur Auszahlung', 'category' => 'frage', 'body' => 'Wann wird ausgezahlt?',
        ])->assertRedirect();
        $ticket = Ticket::firstOrFail();

        // Interner antwortet + interne Notiz
        $this->actingAs($this->operations)->post(route('tickets.reply', $ticket), [
            'body' => 'Die Auszahlung erfolgt nach Zweitfreigabe.',
        ])->assertRedirect();
        $this->actingAs($this->operations)->post(route('tickets.reply', $ticket), [
            'body' => 'Intern: Limite vorher prüfen!', 'is_internal_note' => 1,
        ])->assertRedirect();
        $this->assertSame('beantwortet', $ticket->fresh()->status);

        // Kunde sieht die Antwort, aber NICHT die interne Notiz
        $response = $this->actingAs($kunde)->get(route('tickets.show', $ticket));
        $response->assertOk();
        $response->assertSee('Die Auszahlung erfolgt nach Zweitfreigabe.');
        $response->assertDontSee('Limite vorher prüfen');

        // Fremder Kunde sieht das Ticket nicht
        $otherKunde = User::where('email', 'demo.kunde_sachbearbeitung@aurevia-factoring.de')->first();
        $this->actingAs($otherKunde)->get(route('tickets.show', $ticket))->assertForbidden();
    }

    public function test_facility_special_termination_requires_agreed_right(): void
    {
        $investorOrg = Organization::create([
            'tenant_id' => $this->tenantId, 'org_type' => 'investor', 'name' => 'Invest AG', 'customer_status' => 'Aktiv', 'is_demo' => true,
        ]);
        $facility = Facility::create([
            'tenant_id' => $this->tenantId, 'investor_organization_id' => $investorOrg->id,
            'facility_number' => 'FAC-V3-1', 'name' => 'Testfazilität', 'commitment_amount' => 500000,
            'interest_rate_percent' => 6, 'status' => 'aktiv', 'early_termination_right' => false,
        ]);

        // Sonderkuendigung ohne vereinbartes Recht wird abgelehnt
        $this->actingAs($this->gl)->post(route('facilities.terminate', $facility), [
            'termination_reason' => 'sonderkuendigung',
        ])->assertSessionHasErrors('termination_reason');
        $this->assertSame('aktiv', $facility->fresh()->status);

        // Insolvenz des Investors ist immer moeglich
        $this->actingAs($this->gl)->post(route('facilities.terminate', $facility), [
            'termination_reason' => 'insolvenz_investor', 'note' => 'Insolvenzantrag AG Köln',
        ])->assertRedirect();
        $facility->refresh();
        $this->assertSame('gekuendigt', $facility->status);
        $this->assertSame('insolvenz_investor', $facility->termination_reason);
    }

    public function test_controlling_role_can_manage_costs_but_kunde_cannot(): void
    {
        $controller = User::where('email', 'demo.controlling@aurevia-factoring.de')->first();
        $this->assertNotNull($controller, 'Demo-Nutzer fuer Controlling sollte existieren.');

        $this->actingAs($controller)->get(route('costs.index'))->assertOk();
        $this->actingAs($controller)->post(route('costs.store'), [
            'cost_date' => now()->toDateString(), 'category' => 'it',
            'description' => 'Hosting IONOS', 'amount' => 25.00,
        ])->assertRedirect();

        $kunde = User::where('email', 'demo.kunde_admin@aurevia-factoring.de')->first();
        $this->actingAs($kunde)->get(route('costs.index'))->assertForbidden();
    }

    public function test_changelog_and_faq_pages_render(): void
    {
        $this->actingAs($this->gl)->get(route('help.changelog'))->assertOk()->assertSee('v3.00');
        $this->actingAs($this->gl)->get(route('help.faq'))->assertOk()->assertSee('Marktfolge');
    }
}
