<?php

namespace Tests\Feature;

use App\Http\Controllers\PreviewModeController;
use App\Models\Organization;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * v3.05: Vorschau-Modus — Administration/Geschaeftsleitung sehen die Anwendung
 * aus Sicht anderer Rollen (mit Beispieldaten fuer Kunde/Investor).
 */
class PreviewModeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(DemoUserSeeder::class);
        $this->tenant = Tenant::where('slug', 'aurevia-demo')->firstOrFail();
        $this->admin = User::where('email', 'demo.systemadministration@aurevia-factoring.de')->firstOrFail();
    }

    public function test_admin_can_preview_investor_view_with_sample_data_and_return(): void
    {
        Organization::create([
            'tenant_id' => $this->tenant->id, 'org_type' => 'investor',
            'name' => 'Beispiel-Investor (Testdatensatz)', 'is_demo' => true,
        ]);

        // Start: Wechsel auf das Vorschau-Konto der Rolle Investor
        $this->actingAs($this->admin)
            ->post(route('preview.start', 'investor'))
            ->assertRedirect(route('dashboard'));

        $preview = User::where('email', 'vorschau.investor@aurevia-vorschau.local')->firstOrFail();
        $this->assertAuthenticatedAs($preview);
        $this->assertTrue($preview->hasRole('investor'));
        $this->assertTrue((bool) $preview->is_demo);
        $this->assertNotNull($preview->customer_org_id);

        // Investorrolle wird trotz MFA-Pflicht NICHT in die Einrichtung gezwungen
        // (der echte Administrator hat seine MFA bereits bestanden)
        $this->get(route('dashboard.investor'))->assertOk();

        // Interne Bereiche bleiben fuer die Vorschau-Rolle gesperrt
        $this->get(route('receivables.index'))->assertForbidden();

        // Beenden: zurueck zum urspruenglichen Administrator
        $this->post(route('preview.stop'))->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->admin->fresh());
        $this->assertFalse(session()->has(PreviewModeController::SESSION_KEY));
    }

    public function test_customer_preview_requires_seeded_test_data(): void
    {
        // Ohne Testdaten-Organisation: klare Meldung statt Vorschau
        $this->actingAs($this->admin)
            ->from(route('dashboard'))
            ->post(route('preview.start', 'kunde_admin'))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error');
        $this->assertAuthenticatedAs($this->admin);

        // Mit Testdaten-Organisation funktioniert die Vorschau und bindet die Organisation
        $org = Organization::create([
            'tenant_id' => $this->tenant->id, 'org_type' => 'customer',
            'name' => 'Praxis Dr. Beispiel', 'customer_status' => 'Aktiv', 'is_demo' => true,
        ]);
        $this->post(route('preview.start', 'kunde_admin'))->assertRedirect(route('dashboard'));
        $preview = User::where('email', 'vorschau.kunde_admin@aurevia-vorschau.local')->firstOrFail();
        $this->assertAuthenticatedAs($preview);
        $this->assertSame($org->id, $preview->customer_org_id);
        $this->get(route('dashboard.kunde'))->assertOk();
    }

    public function test_preview_is_restricted_to_admin_roles_and_superadmin_cannot_be_previewed(): void
    {
        $ops = User::where('email', 'demo.operations@aurevia-factoring.de')->firstOrFail();
        $ops->forceFill(['two_factor_secret' => 'test', 'two_factor_confirmed_at' => now()])->save();

        $this->actingAs($ops)->post(route('preview.start', 'investor'))->assertForbidden();

        $this->actingAs($this->admin)->post(route('preview.start', 'superadmin_demo'))->assertForbidden();
        $this->actingAs($this->admin)->post(route('preview.start', 'gibt_es_nicht'))->assertNotFound();

        // Beenden ohne aktiven Vorschau-Modus: 404
        $this->actingAs($this->admin)->post(route('preview.stop'))->assertNotFound();
    }
}
