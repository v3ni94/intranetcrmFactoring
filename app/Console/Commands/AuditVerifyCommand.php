<?php

namespace App\Console\Commands;

use App\Support\AuditLogger;
use Illuminate\Console\Command;

/**
 * Prueft die Hash-Kette des Audit-Logs (Abschnitt 17, append-only-Nachweis).
 * Fuer regelmaessige Integritaetspruefungen per Cron oder manuell.
 */
class AuditVerifyCommand extends Command
{
    protected $signature = 'aurevia:audit-verify';

    protected $description = 'Verifiziert die Hash-Kette des Audit-Logs';

    public function handle(): int
    {
        $broken = AuditLogger::verifyChain();

        if ($broken === []) {
            $this->info('Audit-Kette intakt.');

            return self::SUCCESS;
        }

        $this->error('Kettenbruch bei Audit-Event-ID(s): '.implode(', ', $broken));

        return self::FAILURE;
    }
}
