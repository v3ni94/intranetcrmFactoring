<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Einmalige Produktions-Initialisierung ohne SSH/Tinker (Abschnitt 22):
 * legt Rollen, den Produktiv-Mandanten und den ersten Admin-Benutzer an.
 * Idempotent — mehrfaches Ausfuehren (z.B. ueber einen Webspace-Cronjob)
 * ist unschaedlich, bestehende Daten werden nie ueberschrieben.
 */
class InitCommand extends Command
{
    protected $signature = 'aurevia:init
        {--admin-email= : E-Mail des ersten Admin-Benutzers}
        {--admin-name=Administrator : Anzeigename des ersten Admin-Benutzers}
        {--admin-password= : Passwort (leer = sicheres Zufallspasswort wird erzeugt und angezeigt)}
        {--tenant-name=Aurevia Factoring : Name des Produktiv-Mandanten}';

    protected $description = 'Initialisiert Rollen, Produktiv-Mandant und ersten Admin-Benutzer (idempotent)';

    public function handle(): int
    {
        $this->info('1/3 Rollen anlegen …');
        (new RoleSeeder)->run();

        $this->info('2/3 Produktiv-Mandant anlegen …');
        $tenant = Tenant::firstOrCreate(
            ['slug' => 'aurevia-produktiv'],
            ['name' => $this->option('tenant-name'), 'type' => 'production', 'is_demo' => false],
        );

        $this->info('3/3 Admin-Benutzer anlegen …');
        $email = $this->option('admin-email');
        if (! $email) {
            if (User::role(['geschaeftsleitung', 'systemadministration'])->exists()) {
                $this->info('Admin-Benutzer existiert bereits, nichts zu tun.');

                return self::SUCCESS;
            }
            $this->error('Kein Admin vorhanden. Bitte --admin-email=... angeben.');

            return self::FAILURE;
        }

        if (User::where('email', $email)->exists()) {
            $this->info("Benutzer {$email} existiert bereits, wird nicht überschrieben.");

            return self::SUCCESS;
        }

        $password = $this->option('admin-password');
        $generated = false;
        if (! $password) {
            $password = Str::password(20);
            $generated = true;
        } elseif (strlen($password) < 12) {
            $this->error('Passwort zu kurz (mindestens 12 Zeichen).');

            return self::FAILURE;
        }

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => $this->option('admin-name'),
            'email' => $email,
            'password' => Hash::make($password),
            'is_demo' => false,
        ]);
        $user->syncRoles(['geschaeftsleitung', 'systemadministration']);

        $this->info("Admin-Benutzer {$email} angelegt (Rollen: Geschäftsleitung, Systemadministration).");
        if ($generated) {
            $this->warn("Generiertes Passwort (jetzt notieren, wird nie wieder angezeigt): {$password}");
        }
        $this->line('Beim ersten Login wird die Einrichtung der Zwei-Faktor-Authentifizierung erzwungen.');

        return self::SUCCESS;
    }
}
