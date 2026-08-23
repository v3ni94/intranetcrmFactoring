<?php

namespace App\Services\Integrations;

use App\Models\DunningCase;
use Illuminate\Support\Str;

class CollectionsAdapter extends IntegrationAdapter
{
    protected string $key = 'collections';

    /** Demo-Uebergabe an einen Inkasso-/Rechtsdienstleister. */
    public function handOver(DunningCase $case): string
    {
        $reference = 'INK-DEMO-'.Str::upper(Str::random(8));
        $case->update(['status' => 'inkasso']);

        $this->logSuccess(DunningCase::class, $case->id, $reference, 'An Inkasso-Partner uebergeben (Demo)');

        return $reference;
    }
}
