<?php

namespace App\Services\Integrations;

use App\Models\BankTransaction;
use App\Models\PayoutBatch;

class BankFileAdapter extends IntegrationAdapter
{
    protected string $key = 'bank';

    /** Wird nach Erzeugung einer pain.001-Demo-Datei aufgerufen (siehe SepaExportService). */
    public function logSepaExport(PayoutBatch $batch, string $filename): void
    {
        $this->logSuccess(PayoutBatch::class, $batch->id, $filename, "pain.001-Demo-Datei erzeugt fuer Batch {$batch->batch_number}");
    }

    /** Wird nach einem camt.053-Demo-Import aufgerufen (siehe PaymentMatcher). */
    public function logStatementImport(int $transactionCount): void
    {
        $this->logSuccess(BankTransaction::class, null, null, "camt.053-Demo-Import: {$transactionCount} Kontobewegung(en)");
    }
}
