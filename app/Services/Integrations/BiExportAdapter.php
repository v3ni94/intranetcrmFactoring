<?php

namespace App\Services\Integrations;

use Illuminate\Support\Str;

class BiExportAdapter extends IntegrationAdapter
{
    protected string $key = 'bi_export';

    /** Protokolliert einen Export in Richtung eines BI-/Data-Warehouse-Systems. */
    public function logExport(string $datasetLabel): void
    {
        $this->logSuccess(null, null, 'BI-DEMO-'.Str::upper(Str::random(8)), "Export '{$datasetLabel}' fuer BI/DWH bereitgestellt (Demo)");
    }
}
