<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Benutzerverwaltung: Anlegen mit Rolle und Organisationsbindung,
 * Deaktivieren mit Login-Sperre, Zugriffsschutz.
 */
class UserAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private int $tenantId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(DemoUserSeeder::class);
        $this->tenantId = Tenant::where('slug', 'aurevia-demo')->value('id');
        $this->admin = User::where('email', 'demo.systemadministration@aurevia-factoring.de')->firstOrFail();
    }

    public function test_admin_can_create_internal_user(): void
    {
        $response = $this->actingAs($this->admin)->post(route('users.store'), [
            'name' => 'Neue Mitarbeiterin',
            'email' => 'neu@aurevia-factoring.de',
            'role' => 'operations',
        ]);

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('created_user');

        $user = User::where('email', 'neu@aurevia-factoring.de')->firstOrFail();
        $this->assertTrue($user->hasRole('operations'));
        $this->assertTrue($user->is_active);
        $this->assertNull($user->customer_org_id);
    }

    public function test_customer_user_requires_organization_and_gets_bound_to_it(): void
    {
        // Ohne Organisation abgelehnt
        $this->actingAs($this->admin)->post(route('users.store'), [
            'name' => 'Kunde ohne Org',
            'email' => 'kunde-ohne@praxis.de',
            'role' => 'kunde_admin',
        ])->assertSessionHasErrors('customer_org_id');

        // Mit Organisation gebunden
        $org = Organization::create(['tenant_id' => $this->tenantId, 'org_type' => 'customer', 'name' => 'Praxis Neu', 'customer_status' => 'Aktiv']);
        $this->actingAs($this->admin)->post(route('users.store'), [
            'name' => 'Kunde mit Org',
            'email' => 'kunde-mit@praxis.de',
            'role' => 'kunde_sachbearbeitung',
            'customer_org_id' => $org->id,
        ])->assertRedirect(route('users.index'));

        $this->assertSame($org->id, User::where('email', 'kunde-mit@praxis.de')->value('customer_org_id'));
    }

    public function test_deactivated_user_cannot_log_in(): void
    {
        $user = User::factory()->create([
            'tenant_id' => $this->tenantId,
            'password' => bcrypt('Sicheres-Passwort-1!'),
        ]);
        $user->assignRole('kunde_admin');

        $this->actingAs($this->admin)->post(route('users.toggle-active', $user))->assertRedirect();
        $this->assertFalse($user->fresh()->is_active);

        $this->post('/logout');
        $this->post('/login', ['email' => $user->email, 'password' => 'Sicheres-Passwort-1!'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_admin_cannot_deactivate_own_account(): void
    {
        $this->actingAs($this->admin)->post(route('users.toggle-active', $this->admin))->assertStatus(422);
        $this->assertTrue($this->admin->fresh()->is_active);
    }

    public function test_non_admin_roles_cannot_access_user_management(): void
    {
        foreach (['demo.operations', 'demo.kunde_admin', 'demo.investor'] as $slug) {
            $user = User::where('email', "{$slug}@aurevia-factoring.de")->firstOrFail();
            $this->actingAs($user)->get(route('users.index'))->assertForbidden();
            $this->actingAs($user)->post(route('users.store'), [
                'name' => 'X', 'email' => 'x@x.de', 'role' => 'operations',
            ])->assertForbidden();
        }
    }

    public function test_superadmin_role_can_only_be_granted_by_superadmin(): void
    {
        // Geschaeftsleitung (kein Superadmin) darf die Rolle nicht vergeben.
        $gl = User::where('email', 'demo.geschaeftsleitung@aurevia-factoring.de')->firstOrFail();
        $this->actingAs($gl)->post(route('users.store'), [
            'name' => 'Moechtegern-Admin',
            'email' => 'superneu@aurevia-factoring.de',
            'role' => 'superadmin_demo',
        ])->assertSessionHasErrors('role');

        $this->assertNull(User::where('email', 'superneu@aurevia-factoring.de')->first());
    }

    public function test_locale_switch_changes_language(): void
    {
        $this->get(route('locale.switch', 'en'), ['HTTP_REFERER' => '/login']);
        $response = $this->get('/login');
        $response->assertSee('Sign in');

        $this->get(route('locale.switch', 'de'), ['HTTP_REFERER' => '/login']);
        $response = $this->get('/login');
        $response->assertSee('Anmelden');
    }
}
