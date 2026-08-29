<?php

namespace Tests\Feature;

use App\Models\Organization;
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

    public function test_investor_role_binds_to_investor_organization_and_unbinds_on_role_change(): void
    {
        $investorOrg = Organization::create([
            'tenant_id' => $this->tenantId, 'org_type' => 'investor', 'name' => 'Kapitalgeber Test AG',
        ]);
        $customerOrg = Organization::create([
            'tenant_id' => $this->tenantId, 'org_type' => 'customer', 'name' => 'Praxis Bindungstest', 'customer_status' => 'Aktiv',
        ]);
        $user = $this->makeUser();

        // Ohne Organisation abgelehnt
        $this->actingAs($this->admin)->post(route('users.update', $user), [
            'name' => $user->name, 'email' => $user->email, 'role' => 'investor',
        ])->assertSessionHasErrors('customer_org_id');

        // Kundenorganisation passt nicht zur Investor-Rolle
        $this->actingAs($this->admin)->post(route('users.update', $user), [
            'name' => $user->name, 'email' => $user->email, 'role' => 'investor',
            'customer_org_id' => $customerOrg->id,
        ])->assertSessionHasErrors('customer_org_id');

        // Mit Investor-Organisation gebunden
        $this->actingAs($this->admin)->post(route('users.update', $user), [
            'name' => $user->name, 'email' => $user->email, 'role' => 'investor',
            'customer_org_id' => $investorOrg->id,
        ])->assertRedirect();
        $user->refresh();
        $this->assertTrue($user->hasRole('investor'));
        $this->assertSame($investorOrg->id, $user->customer_org_id);

        // Rollenwechsel weg von organisationsgebundenen Rollen loest die Bindung
        $this->actingAs($this->admin)->post(route('users.update', $user), [
            'name' => $user->name, 'email' => $user->email, 'role' => 'operations',
        ])->assertRedirect();
        $this->assertNull($user->fresh()->customer_org_id);

        // Umgekehrt: Kundenrolle akzeptiert keine Investor-Organisation
        $this->actingAs($this->admin)->post(route('users.update', $user), [
            'name' => $user->name, 'email' => $user->email, 'role' => 'kunde_admin',
            'customer_org_id' => $investorOrg->id,
        ])->assertSessionHasErrors('customer_org_id');
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

    public function test_knowledge_base_renders_process_manual_for_internal_role_only(): void
    {
        $customer = User::where('email', 'demo.kunde_admin@aurevia-factoring.de')->firstOrFail();

        $this->actingAs($customer)->get(route('help.knowledge', 'prozesshandbuch'))->assertForbidden();

        $response = $this->actingAs($this->admin)->get(route('help.knowledge', 'prozesshandbuch'));
        $response->assertOk();
        $response->assertSee('Vertrieb und Marketing', false);
        $response->assertSee('Ergänzende Prozesse', false);
    }

    /** v3.04: Nachweis-Dokumente (Perso, Fuehrerschein, SCHUFA, Fuehrungszeugnis) hochladen, laden, loeschen. */
    public function test_hr_documents_can_be_uploaded_downloaded_and_deleted(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        $employee = $this->makeUser();

        // Upload durch die Administration
        $file = \Illuminate\Http\Testing\File::fake()->create('perso.pdf', 120, 'application/pdf');
        $this->actingAs($this->admin)
            ->post(route('users.documents.upload', $employee), ['doc_type' => 'personalausweis', 'file' => $file])
            ->assertRedirect();

        $document = \App\Models\HrDocument::where('user_id', $employee->id)->firstOrFail();
        $this->assertSame('personalausweis', $document->doc_type);
        $this->assertSame('perso.pdf', $document->original_name);
        \Illuminate\Support\Facades\Storage::disk('local')->assertExists($document->storage_path);

        // Anzeige auf der Bearbeiten-Seite und Download
        $this->actingAs($this->admin)->get(route('users.edit', $employee))
            ->assertOk()->assertSee('perso.pdf');
        $this->actingAs($this->admin)->get(route('users.documents.download', [$employee, $document]))
            ->assertOk()->assertDownload('perso.pdf');

        // Unzulaessiger Dateityp wird abgewiesen
        $bad = \Illuminate\Http\Testing\File::fake()->create('makro.docx', 50, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        $this->actingAs($this->admin)
            ->post(route('users.documents.upload', $employee), ['doc_type' => 'schufa', 'file' => $bad])
            ->assertSessionHasErrors('file');

        // Loeschen entfernt Datensatz und Datei
        $path = $document->storage_path;
        $this->actingAs($this->admin)
            ->post(route('users.documents.destroy', [$employee, $document]))
            ->assertRedirect();
        $this->assertDatabaseMissing('hr_documents', ['id' => $document->id]);
        \Illuminate\Support\Facades\Storage::disk('local')->assertMissing($path);
    }

    /** Nachweise sind fuer Rollen ohne Benutzerverwaltungsrecht unerreichbar; kein Fremdzugriff per URL-Raten. */
    public function test_hr_documents_are_forbidden_for_non_admin_roles(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        $employee = $this->makeUser();

        $file = \Illuminate\Http\Testing\File::fake()->create('schufa.pdf', 80, 'application/pdf');
        $this->actingAs($this->admin)
            ->post(route('users.documents.upload', $employee), ['doc_type' => 'schufa', 'file' => $file])
            ->assertRedirect();
        $document = \App\Models\HrDocument::where('user_id', $employee->id)->firstOrFail();

        // Der Mitarbeiter selbst (Rolle operations, 2FA abgeschlossen) darf
        // seine eigene Akte nicht abrufen — Rollenpruefung greift serverseitig.
        $employee->forceFill(['two_factor_secret' => 'test-secret', 'two_factor_confirmed_at' => now()])->save();
        $this->actingAs($employee)
            ->get(route('users.documents.download', [$employee, $document]))
            ->assertForbidden();

        // Dokument haengt am falschen Benutzer-Pfad: 404 statt Auslieferung
        $other = $this->makeUser();
        $this->actingAs($this->admin)
            ->get(route('users.documents.download', [$other, $document]))
            ->assertNotFound();
    }
}
