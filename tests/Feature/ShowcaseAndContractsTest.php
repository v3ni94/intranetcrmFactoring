<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\Document;
use App\Models\Facility;
use App\Models\FacilityEvent;
use App\Models\FactoringProduct;
use App\Models\OperatingCost;
use App\Models\Organization;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ShowcaseDataService;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * v3.03: Vorfuehr-Testdaten (Einspielen/Loeschen mit Passwortschutz) und
 * Mustervertraege mit einfacher elektronischer Signatur.
 */
class ShowcaseAndContractsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->seed(RoleSeeder::class);
        $this->seed(DemoUserSeeder::class);
        $this->tenant = Tenant::where('slug', 'aurevia-demo')->firstOrFail();
        $this->admin = User::where('email', 'demo.superadmin@aurevia-factoring.de')->first()
            ?? User::where('email', 'demo.systemadministration@aurevia-factoring.de')->firstOrFail();
    }

    public function test_showcase_seed_creates_full_dataset_and_purge_removes_it(): void
    {
        $service = app(ShowcaseDataService::class);
        $created = $service->seed($this->tenant, $this->admin);

        $this->assertGreaterThan(500, $created);

        // 100 fiktive Kunden, 3 Investoren mit Ausschuettungshistorie
        $this->assertSame(100, Organization::where('org_type', 'customer')->where('is_demo', true)->count());
        $this->assertSame(3, Facility::where('is_demo', true)->count());

        $apo = Facility::where('facility_number', 'FAC-TEST-'.$this->tenant->id.'-3')->firstOrFail();
        $this->assertSame(29500000.0, (float) $apo->commitment_amount);
        $this->assertSame(10.0, (float) $apo->interest_rate_percent);
        $this->assertGreaterThan(5, FacilityEvent::where('facility_id', $apo->id)->where('event_type', 'zinszahlung')->count());

        // Kosten seit 2025, unterschriebene Mustervertraege (5 Kunden + 3 Investoren)
        $this->assertGreaterThan(100, OperatingCost::where('is_demo', true)->count());
        $signed = Document::where('category', 'vertrag')->whereNotNull('signed_company_at')->whereNotNull('signed_counterparty_at')->count();
        $this->assertSame(8, $signed);

        // Erneutes Einspielen ist idempotent
        $this->assertSame(0, $service->seed($this->tenant, $this->admin));

        // Nur-Testdaten-Loeschung entfernt alles, Nutzer bleiben
        $userCount = User::count();
        $service->purgeTestData($this->tenant);
        $this->assertSame(0, $service->countTestRecords($this->tenant));
        $this->assertSame(0, Organization::where('is_demo', true)->count());
        $this->assertSame($userCount, User::count());
    }

    public function test_generate_and_sign_customer_contract_flow(): void
    {
        $gl = User::where('email', 'demo.geschaeftsleitung@aurevia-factoring.de')->firstOrFail();

        $org = Organization::create([
            'tenant_id' => $this->tenant->id, 'org_type' => 'customer',
            'name' => 'Praxis Signaturtest', 'customer_status' => 'Aktiv',
        ]);
        $product = FactoringProduct::create([
            'tenant_id' => $this->tenant->id, 'name' => 'Testprodukt', 'recourse_type' => 'unecht_mit_regress',
            'service_type' => 'full_service', 'disclosure_type' => 'offen', 'scope_type' => 'gesamtumsatz', 'active' => true,
        ]);
        $contract = Contract::create([
            'tenant_id' => $this->tenant->id, 'organization_id' => $org->id, 'factoring_product_id' => $product->id,
            'contract_number' => 'AUR-SIG-0001', 'start_date' => now()->subMonth(), 'term_months' => 24,
            'notice_period_days' => 90, 'status' => 'aktiv', 'purchase_line' => 100000, 'payout_line' => 87000,
            'advance_rate_percent' => 85, 'reserve_percent' => 15, 'factoring_fee_percent' => 1.5,
            'reference_rate_percent' => 3, 'margin_percent' => 2.5, 'max_days_outstanding' => 120,
            'recourse_period_days' => 90, 'day_count_convention' => 'act/360',
        ]);

        // Erzeugen (intern)
        $this->actingAs($gl)->post(route('contracts.generate', $contract))->assertRedirect(route('documents.index'));
        $document = Document::where('category', 'vertrag')->latest('id')->firstOrFail();
        $this->assertSame('extern_freigegeben', $document->visibility);
        $this->assertTrue(Storage::disk('local')->exists($document->storage_path));

        // Gesellschaft unterzeichnet (nur GL/Superadmin)
        $ops = User::where('email', 'demo.operations@aurevia-factoring.de')->firstOrFail();
        $this->actingAs($ops)->post(route('documents.sign', $document), [
            'side' => 'company', 'signer_name' => 'Unbefugt', 'confirm' => '1',
        ])->assertForbidden();

        $this->actingAs($gl)->post(route('documents.sign', $document), [
            'side' => 'company', 'signer_name' => 'Timo Müller', 'confirm' => '1',
        ])->assertRedirect();
        $this->assertNotNull($document->fresh()->signed_company_at);

        // Kunde der eigenen Organisation unterzeichnet die Gegenseite
        $customer = User::where('email', 'demo.kunde_admin@aurevia-factoring.de')->firstOrFail();
        $customer->update(['customer_org_id' => $org->id]);

        $this->actingAs($customer)->post(route('documents.sign', $document), [
            'side' => 'counterparty', 'signer_name' => 'Dr. Signaturtest', 'confirm' => '1',
        ])->assertRedirect();

        $document->refresh();
        $this->assertTrue($document->isFullySigned());
        $this->assertSame(3, $document->version); // Erzeugt + 2x Signatur-Rerender
    }

    public function test_purge_all_requires_phrase_and_password(): void
    {
        $super = User::where('email', 'demo.superadmin@aurevia-factoring.de')->first();
        if (! $super) {
            $super = $this->admin;
            $super->assignRole('superadmin_demo');
        }

        Organization::create([
            'tenant_id' => $this->tenant->id, 'org_type' => 'customer',
            'name' => 'Eigene Praxis (kein Testdatensatz)', 'customer_status' => 'Aktiv',
        ]);

        // Falsche Phrase
        $this->actingAs($super)->post(route('demo.purge-all'), [
            'confirmation' => 'LÖSCHEN', 'password' => DemoUserSeeder::DEMO_PASSWORD,
        ])->assertStatus(422);

        // Falsches Passwort
        $this->actingAs($super)->post(route('demo.purge-all'), [
            'confirmation' => 'ALLES LÖSCHEN', 'password' => 'falsch',
        ])->assertForbidden();

        $this->assertSame(1, Organization::where('org_type', 'customer')->count());

        // Korrekt: loescht auch selbst angelegte Daten
        $this->actingAs($super)->post(route('demo.purge-all'), [
            'confirmation' => 'ALLES LÖSCHEN', 'password' => DemoUserSeeder::DEMO_PASSWORD,
        ])->assertRedirect();

        $this->assertSame(0, Organization::count());
        $this->assertGreaterThan(0, User::count());
    }

    /**
     * Regression v3.03.1: Die Demo-Steuerungsseite selbst muss ohne Serverfehler
     * laden — vor und nach dem Einspielen. Die Zaehlung der Testdatensaetze darf
     * die is_demo-Spalte nur auf Tabellen abfragen, die sie besitzen (auf MariaDB
     * fuehrte die ungefilterte Abfrage zu einem 500er auf der Seite).
     */
    public function test_demo_control_page_renders_before_and_after_seeding(): void
    {
        $super = User::where('email', 'demo.superadmin@aurevia-factoring.de')->first();
        if (! $super) {
            $super = $this->admin;
            $super->assignRole('superadmin_demo');
        }

        $this->actingAs($super)->get(route('demo.index'))->assertOk();

        $service = app(ShowcaseDataService::class);
        $created = $service->seed($this->tenant, $super);
        $this->assertGreaterThan(500, $created);

        // Nach dem Einspielen zeigt die Seite die Testdaten an und zaehlt sie korrekt
        $this->actingAs($super)->get(route('demo.index'))->assertOk();
        $this->assertSame($created, $service->countTestRecords($this->tenant));
    }
}
