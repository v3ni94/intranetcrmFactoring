<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\User;
use App\Services\ShowcaseDataService;
use Illuminate\Console\Command;

/**
 * v3.03: Vorfuehr-Testdaten per CLI/Update-Skript einspielen oder loeschen.
 *   php artisan aurevia:showcase           — einspielen (idempotent)
 *   php artisan aurevia:showcase --purge   — nur Testdaten loeschen
 */
class ShowcaseCommand extends Command
{
    protected $signature = 'aurevia:showcase {--purge : Nur als Testdaten markierte Datensätze löschen}';

    protected $description = 'Spielt die fiktiven Vorführ-Testdaten ein (bzw. löscht sie mit --purge)';

    public function handle(ShowcaseDataService $service): int
    {
        $tenant = Tenant::orderBy('id')->firstOrFail();
        $actor = User::where('tenant_id', $tenant->id)->orderBy('id')->firstOrFail();

        if ($this->option('purge')) {
            $affected = $service->purgeTestData($tenant);
            $this->info("Testdaten gelöscht: {$affected} Datensätze.");

            return self::SUCCESS;
        }

        if ($service->hasShowcaseData($tenant)) {
            $this->warn('Es sind bereits Testdaten vorhanden — nichts zu tun (erst --purge, dann neu einspielen).');

            return self::SUCCESS;
        }

        $created = $service->seed($tenant, $actor);
        $this->info("Testdaten eingespielt: {$created} Datensätze.");

        return self::SUCCESS;
    }
}
