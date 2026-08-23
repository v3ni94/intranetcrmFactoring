<?php

namespace App\Services\Integrations;

use App\Models\BeneficialOwner;
use Illuminate\Support\Str;

class PepSanctionsAdapter extends IntegrationAdapter
{
    protected string $key = 'pep_sanctions';

    /**
     * Sandbox-Screening: immer negativ (kein Treffer), da synthetische Demo-Personen.
     * Reale Implementierung wuerde hier einen Wirtschaftsauskunfts-/Sanktionslisten-Anbieter aufrufen.
     */
    public function screen(BeneficialOwner $owner): BeneficialOwner
    {
        $owner->update(['pep_status' => false, 'sanctions_hit' => false, 'screened_at' => now()]);

        $this->logSuccess(
            BeneficialOwner::class,
            $owner->id,
            'SCR-SANDBOX-'.Str::upper(Str::random(8)),
            'PEP-/Sanktionsscreening ohne Treffer (Sandbox)'
        );

        return $owner;
    }
}
