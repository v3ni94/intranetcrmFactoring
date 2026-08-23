<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

/**
 * Abschnitt 18: "MFA zwingend fuer alle internen, Investor-, Beirats- und
 * Admin-Nutzer." Kunden-Rollen sind im Prototyp ausgenommen.
 */
class TwoFactorAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->tenant = Tenant::create(['name' => 'Test-Mandant', 'slug' => 'test-tenant', 'type' => 'demo', 'is_demo' => true]);
        TenantContext::set($this->tenant->id);
    }

    private function makeUser(string $role): User
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id, 'password' => bcrypt('secret-password')]);
        $user->assignRole($role);

        return $user;
    }

    public function test_internal_user_without_mfa_is_forced_to_setup_after_login(): void
    {
        $user = $this->makeUser('operations');

        $response = $this->post('/login', ['email' => $user->email, 'password' => 'secret-password']);

        $response->assertRedirect(route('two-factor.setup'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_kunde_role_is_not_forced_into_mfa(): void
    {
        $user = $this->makeUser('kunde_admin');

        $response = $this->post('/login', ['email' => $user->email, 'password' => 'secret-password']);

        $response->assertRedirect(route('dashboard'));
    }

    public function test_login_of_confirmed_internal_user_requires_valid_totp_code(): void
    {
        $google2fa = new Google2FA;
        $secret = $google2fa->generateSecretKey();

        $user = $this->makeUser('kredit_risiko');
        $user->forceFill(['two_factor_secret' => $secret, 'two_factor_confirmed_at' => now()])->save();

        // Erstfaktor korrekt, aber danach nicht mehr voll angemeldet.
        $response = $this->post('/login', ['email' => $user->email, 'password' => 'secret-password']);
        $response->assertRedirect(route('two-factor.challenge'));
        $this->assertGuest();

        // Falscher Code scheitert.
        $this->post('/zwei-faktor', ['code' => '000000'])->assertSessionHasErrors('code');
        $this->assertGuest();

        // Gueltiger Code schliesst den Login ab.
        $validCode = $google2fa->getCurrentOtp($secret);
        $this->post('/zwei-faktor', ['code' => $validCode])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_middleware_blocks_navigation_until_mfa_setup_confirmed(): void
    {
        $user = $this->makeUser('compliance');

        $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('two-factor.setup'));
    }
}
