<?php

namespace App\Services\Integrations;

use Illuminate\Support\Str;

class PraxisImportAdapter extends IntegrationAdapter
{
    protected string $key = 'praxis_import';

    /** Protokolliert einen Datei-Import aus einem Praxis-/Abrechnungssystem (Demo: manueller Upload). */
    public function logImport(int $rowCount, string $sourceLabel): void
    {
        $this->logSuccess(null, null, 'IMP-DEMO-'.Str::upper(Str::random(8)), "{$rowCount} Datensaetze importiert aus {$sourceLabel} (Demo)");
    }
}
