<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
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

    public function test_login_with_recovery_code_works_once_and_consumes_the_code(): void
    {
        $google2fa = new Google2FA;
        $user = $this->makeUser('operations');
        $user->forceFill([
            'two_factor_secret' => $google2fa->generateSecretKey(),
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => [Hash::make('RESCUE1234'), Hash::make('RESCUE5678')],
        ])->save();

        $this->post('/login', ['email' => $user->email, 'password' => 'secret-password']);
        $this->post('/zwei-faktor', ['code' => 'RESCUE1234'])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertCount(1, $user->fresh()->two_factor_recovery_codes, 'Der verbrauchte Code muss entfernt sein.');

        // Derselbe Code funktioniert kein zweites Mal.
        $this->post('/logout');
        $this->post('/login', ['email' => $user->email, 'password' => 'secret-password']);
        $this->post('/zwei-faktor', ['code' => 'RESCUE1234'])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_too_many_wrong_mfa_codes_abort_the_challenge(): void
    {
        $google2fa = new Google2FA;
        $user = $this->makeUser('operations');
        $user->forceFill([
            'two_factor_secret' => $google2fa->generateSecretKey(),
            'two_factor_confirmed_at' => now(),
        ])->save();

        $this->post('/login', ['email' => $user->email, 'password' => 'secret-password']);

        foreach (range(1, 5) as $i) {
            $this->post('/zwei-faktor', ['code' => '000000'])->assertSessionHasErrors('code');
        }

        // Sechster Versuch: Challenge wird verworfen, zurueck zum Login.
        $this->post('/zwei-faktor', ['code' => '000000'])->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_totp_code_cannot_be_replayed_after_successful_login(): void
    {
        $google2fa = new Google2FA;
        $secret = $google2fa->generateSecretKey();
        $user = $this->makeUser('operations');
        $user->forceFill(['two_factor_secret' => $secret, 'two_factor_confirmed_at' => now()])->save();

        $code = $google2fa->getCurrentOtp($secret);

        $this->post('/login', ['email' => $user->email, 'password' => 'secret-password']);
        $this->post('/zwei-faktor', ['code' => $code])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);

        // Abmelden und denselben Code erneut verwenden: Replay muss scheitern.
        $this->post('/logout');
        $this->post('/login', ['email' => $user->email, 'password' => 'secret-password']);
        $this->post('/zwei-faktor', ['code' => $code])->assertSessionHasErrors('code');
        $this->assertGuest();
    }
}
