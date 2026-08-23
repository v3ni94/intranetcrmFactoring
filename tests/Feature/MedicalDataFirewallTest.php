<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Organization;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Abschnitt 16.2 (Medical Data Firewall) und Abnahmekriterium 6: Investor, Beirat und
 * Kunde duerfen Kunden-/Debitorenidentitaeten und nicht freigegebene Dokumente ueber
 * keinen direkten URL-Aufruf erreichen, unabhaengig von der Navigationsanzeige.
 */
class MedicalDataFirewallTest extends TestCase
{
    use RefreshDatabase;

    private int $tenantId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(DemoUserSeeder::class);
        $this->tenantId = Tenant::where('slug', 'aurevia-demo')->value('id');
    }

    public function test_investor_and_beirat_and_kunde_cannot_reach_internal_customer_lists(): void
    {
        foreach (['demo.investor', 'demo.beirat', 'demo.kunde_admin'] as $slug) {
            $user = User::where('email', "{$slug}@aurevia-factoring.de")->firstOrFail();

            $this->actingAs($user)->get(route('organizations.index'))->assertForbidden();
            $this->actingAs($user)->get(route('debtors.index'))->assertForbidden();
            $this->actingAs($user)->get(route('receivables.index'))->assertForbidden();
        }
    }

    public function test_internal_role_can_reach_customer_list(): void
    {
        $operations = User::where('email', 'demo.operations@aurevia-factoring.de')->firstOrFail();

        $this->actingAs($operations)->get(route('organizations.index'))->assertOk();
    }

    public function test_only_externally_released_documents_are_visible_to_investor(): void
    {
        Document::create(['tenant_id' => $this->tenantId, 'title' => 'Internes Memo', 'category' => 'sonstiges', 'visibility' => 'intern']);
        Document::create(['tenant_id' => $this->tenantId, 'title' => 'Board Pack Q3', 'category' => 'board_pack', 'visibility' => 'extern_freigegeben']);

        $investor = User::where('email', 'demo.investor@aurevia-factoring.de')->firstOrFail();

        $response = $this->actingAs($investor)->get(route('documents.index'));
        $response->assertOk();
        $response->assertSee('Board Pack Q3');
        $response->assertDontSee('Internes Memo');
    }

    public function test_investor_cannot_download_document_not_released_to_them(): void
    {
        $document = Document::create(['tenant_id' => $this->tenantId, 'title' => 'Vertraulich', 'category' => 'sonstiges', 'visibility' => 'vertraulich', 'storage_path' => 'documents/test.txt']);
        $investor = User::where('email', 'demo.investor@aurevia-factoring.de')->firstOrFail();

        $this->actingAs($investor)->get(route('documents.download', $document))->assertForbidden();
    }

    public function test_kunde_only_sees_externally_released_documents_of_own_organization(): void
    {
        $ownOrg = Organization::create(['tenant_id' => $this->tenantId, 'org_type' => 'customer', 'name' => 'Eigene Praxis', 'customer_status' => 'Aktiv', 'is_demo' => true]);
        $otherOrg = Organization::create(['tenant_id' => $this->tenantId, 'org_type' => 'customer', 'name' => 'Andere Praxis', 'customer_status' => 'Aktiv', 'is_demo' => true]);

        Document::create(['tenant_id' => $this->tenantId, 'title' => 'Eigener Vertrag', 'category' => 'vertrag', 'visibility' => 'extern_freigegeben', 'related_type' => Organization::class, 'related_id' => $ownOrg->id]);
        Document::create(['tenant_id' => $this->tenantId, 'title' => 'Fremder Vertrag', 'category' => 'vertrag', 'visibility' => 'extern_freigegeben', 'related_type' => Organization::class, 'related_id' => $otherOrg->id]);

        $kunde = User::where('email', 'demo.kunde_admin@aurevia-factoring.de')->firstOrFail();
        $kunde->update(['customer_org_id' => $ownOrg->id]);

        $response = $this->actingAs($kunde)->get(route('documents.index'));
        $response->assertOk();
        $response->assertSee('Eigener Vertrag');
        $response->assertDontSee('Fremder Vertrag');
    }

    public function test_kunde_and_investor_cannot_upload_documents(): void
    {
        foreach (['demo.investor', 'demo.kunde_admin'] as $slug) {
            $user = User::where('email', "{$slug}@aurevia-factoring.de")->firstOrFail();

            $this->actingAs($user)->post(route('documents.store'), [
                'title' => 'Sollte scheitern', 'category' => 'sonstiges', 'visibility' => 'extern_freigegeben',
            ])->assertForbidden();
        }
    }
}
