<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Produktionspfad (Abschnitt 22): frische Migration OHNE Demo-Seeder,
 * danach aurevia:init — Rollen, Mandant und erster Admin muessen entstehen
 * und der Admin muss sich einloggen koennen (bis zur MFA-Einrichtung).
 */
class InitCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_init_creates_roles_tenant_and_admin_on_fresh_database(): void
    {
        $this->artisan('aurevia:init', [
            '--admin-email' => 'admin@example.com',
            '--admin-name' => 'Erst-Admin',
            '--admin-password' => 'Sehr-sicheres-Passwort-1!',
        ])->assertExitCode(0);

        $tenant = Tenant::where('slug', 'aurevia-produktiv')->first();
        $this->assertNotNull($tenant);
        $this->assertFalse($tenant->is_demo);

        $admin = User::where('email', 'admin@example.com')->first();
        $this->assertNotNull($admin);
        $this->assertTrue($admin->hasRole('geschaeftsleitung'));
        $this->assertTrue($admin->hasRole('systemadministration'));

        // Login funktioniert; interner Nutzer ohne MFA landet im Einrichtungszwang.
        $response = $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'Sehr-sicheres-Passwort-1!',
        ]);
        $response->assertRedirect();
        $this->assertAuthenticated();
        $this->get(route('dashboard.geschaeftsleitung'))->assertRedirect(route('two-factor.setup'));
    }

    public function test_init_is_idempotent_and_never_overwrites(): void
    {
        $this->artisan('aurevia:init', [
            '--admin-email' => 'admin@example.com',
            '--admin-password' => 'Sehr-sicheres-Passwort-1!',
        ])->assertExitCode(0);

        $this->artisan('aurevia:init', [
            '--admin-email' => 'admin@example.com',
            '--admin-password' => 'Anderes-Passwort-Wird-Ignoriert-2!',
        ])->assertExitCode(0);

        $this->assertSame(1, User::where('email', 'admin@example.com')->count());
        $this->assertSame(1, Tenant::where('slug', 'aurevia-produktiv')->count());

        // Erstes Passwort gilt weiterhin.
        $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'Sehr-sicheres-Passwort-1!',
        ]);
        $this->assertAuthenticated();
    }

    public function test_init_rejects_short_password(): void
    {
        $this->artisan('aurevia:init', [
            '--admin-email' => 'admin@example.com',
            '--admin-password' => 'kurz',
        ])->assertExitCode(1);

        $this->assertNull(User::where('email', 'admin@example.com')->first());
    }
}
