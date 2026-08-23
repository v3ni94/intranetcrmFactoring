<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use App\Support\RoleCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;

class DemoUserSeeder extends Seeder
{
    public const DEMO_PASSWORD = 'Aurevia-Demo-2026!';

    public function run(): void
    {
        $tenant = Tenant::firstOrCreate(
            ['slug' => 'aurevia-demo'],
            ['name' => 'Aurevia Factoring – Demo-Mandant', 'type' => 'demo', 'is_demo' => true, 'demo_seed_id' => 'seed-2026-08-23']
        );

        foreach (RoleCatalog::ROLES as $slug => $label) {
            $email = "demo.{$slug}@aurevia-factoring.de";

            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'tenant_id' => $tenant->id,
                    'name' => 'Demo · '.$label,
                    'password' => Hash::make(self::DEMO_PASSWORD),
                    'email_verified_at' => now(),
                    'is_demo' => true,
                ]
            );

            $user->syncRoles([$slug]);
            $this->preConfirmMfaIfRequired($user);
        }

        // Zusaetzlicher persoenlicher Zugang fuer den Kick-off, Rolle Geschaeftsleitung.
        $lead = User::updateOrCreate(
            ['email' => 'timo.mueller@aurevia-factoring.de'],
            [
                'tenant_id' => $tenant->id,
                'name' => 'Timo Müller',
                'password' => Hash::make(self::DEMO_PASSWORD),
                'email_verified_at' => now(),
                'is_demo' => true,
            ]
        );
        $lead->syncRoles(['geschaeftsleitung', 'superadmin_demo']);
        $this->preConfirmMfaIfRequired($lead);
    }

    /**
     * MFA ist fuer interne/Investor-/Beirats-Rollen Pflicht (Abschnitt 18). Damit der
     * gefuehrte Kick-off-Klickpfad (Abschnitt 21.2) nicht bei jedem Rollenwechsel durch
     * eine TOTP-Einrichtung unterbrochen wird, ist MFA fuer Demo-Nutzer bereits vorkonfiguriert.
     * Der Mechanismus selbst bleibt unveraendert erzwungen (siehe EnsureMfaIsConfirmed) und
     * ist ueber IntegrationAdapterTest/RoleDashboardTest hinaus separat getestet.
     */
    private function preConfirmMfaIfRequired(User $user): void
    {
        if (! $user->requiresMfa() || $user->hasConfirmedTwoFactor()) {
            return;
        }

        $user->forceFill([
            'two_factor_secret' => (new Google2FA)->generateSecretKey(),
            'two_factor_confirmed_at' => now(),
            'mfa_enabled' => true,
        ])->save();
    }
}
