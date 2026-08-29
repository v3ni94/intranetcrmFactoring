<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * v3.02: Personalakte, Beschaeftigungsfenster, Benutzer bearbeiten/loeschen,
 * Wissensdatenbank-Zugriffsschutz.
 */
class PersonnelFileTest extends TestCase
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

    private function makeUser(array $attributes = [], string $role = 'operations'): User
    {
        $user = User::factory()->create(array_merge([
            'tenant_id' => $this->tenantId,
            'password' => bcrypt('Sicheres-Passwort-1!'),
        ], $attributes));
        $user->assignRole($role);

        return $user;
    }

    public function test_login_blocked_before_joining_date_and_after_leaving_date(): void
    {
        $future = $this->makeUser(['joined_at' => now()->addDays(10)]);
        $this->post('/login', ['email' => $future->email, 'password' => 'Sicheres-Passwort-1!'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();

        $left = $this->makeUser(['joined_at' => now()->subYear(), 'left_at' => now()->subDays(3)]);
        $this->post('/login', ['email' => $left->email, 'password' => 'Sicheres-Passwort-1!'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->assertSame('wartet_auf_eintritt', $future->fresh()->effectiveStatus());
        $this->assertSame('ausgetreten', $left->fresh()->effectiveStatus());
    }

    public function test_admin_can_update_personnel_file_and_encrypted_fields_are_hidden(): void
    {
        $user = $this->makeUser();

        $this->actingAs($this->admin)->post(route('users.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'role' => 'operations',
            'position' => 'Sachbearbeitung Factoring',
            'department' => 'Operations',
            'street' => 'Musterweg 1',
            'zip' => '50667',
            'city' => 'Köln',
            'birth_date' => '1990-05-04',
            'tax_id' => '12 345 678 901',
            'id_card_number' => 'L01X00T47',
            'joined_at' => '2025-01-01',
        ])->assertRedirect();

        $user->refresh();
        $this->assertSame('Sachbearbeitung Factoring', $user->position);
        $this->assertSame('12 345 678 901', $user->tax_id);
        // Verschluesselt in der DB (kein Klartext) und in Arrays ausgeblendet
        $raw = \DB::table('users')->where('id', $user->id)->value('tax_id');
        $this->assertNotSame('12 345 678 901', $raw);
        $this->assertArrayNotHasKey('tax_id', $user->toArray());
        $this->assertArrayNotHasKey('id_card_number', $user->toArray());
    }

    public function test_role_swap_works_but_superadmin_grant_requires_superadmin(): void
    {
        $user = $this->makeUser();

        // Normaler Rollentausch
        $this->actingAs($this->admin)->post(route('users.update', $user), [
            'name' => $user->name, 'email' => $user->email, 'role' => 'treasury_finance',
        ])->assertRedirect();
        $this->assertTrue($user->fresh()->hasRole('treasury_finance'));

        // Superadmin-Vergabe durch Nicht-Superadmin abgelehnt
        $this->actingAs($this->admin)->post(route('users.update', $user), [
            'name' => $user->name, 'email' => $user->email, 'role' => 'superadmin_demo',
        ])->assertSessionHasErrors('role');
        $this->assertFalse($user->fresh()->hasRole('superadmin_demo'));

        // Superadmin-Entzug durch Nicht-Superadmin abgelehnt
        $super = $this->makeUser([], 'superadmin_demo');
        $this->actingAs($this->admin)->post(route('users.update', $super), [
            'name' => $super->name, 'email' => $super->email, 'role' => 'operations',
        ])->assertSessionHasErrors('role');
        $this->assertTrue($super->fresh()->hasRole('superadmin_demo'));
    }

    public function test_user_without_history_can_be_deleted_but_with_history_cannot(): void
    {
        $fresh = $this->makeUser();
        $this->actingAs($this->admin)->post(route('users.destroy', $fresh))
            ->assertRedirect(route('users.index'));
        $this->assertNull(User::find($fresh->id));

        $withHistory = $this->makeUser();
        Ticket::create([
            'tenant_id' => $this->tenantId,
            'ticket_number' => 'T-TEST-1',
            'subject' => 'Historie',
            'category' => 'frage',
            'status' => 'offen',
            'priority' => 'normal',
            'created_by' => $withHistory->id,
        ]);

        $this->actingAs($this->admin)->post(route('users.destroy', $withHistory))
            ->assertSessionHasErrors('delete');
        $this->assertNotNull(User::find($withHistory->id));
    }

    public function test_edit_page_renders_for_admin_only(): void
    {
        $user = $this->makeUser();
        $this->actingAs($this->admin)->get(route('users.edit', $user))->assertOk();

        $ops = User::where('email', 'demo.operations@aurevia-factoring.de')->firstOrFail();
        $this->actingAs($ops)->get(route('users.edit', $user))->assertForbidden();
    }

    public function test_knowledge_base_internal_docs_require_internal_role(): void
    {
        $customer = User::where('email', 'demo.kunde_admin@aurevia-factoring.de')->firstOrFail();

        $this->actingAs($customer)->get(route('help.knowledge', 'handbuch'))->assertOk();
        $this->actingAs($customer)->get(route('help.knowledge', 'bafin'))->assertForbidden();
        $this->actingAs($customer)->get(route('help.knowledge', 'datenschutz'))->assertForbidden();

        $this->actingAs($this->admin)->get(route('help.knowledge', 'bafin'))->assertOk();
        $this->actingAs($this->admin)->get(route('help.onboarding'))->assertOk();
    }
}
