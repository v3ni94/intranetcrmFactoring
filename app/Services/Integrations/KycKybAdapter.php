<?php

namespace App\Services\Integrations;

use App\Models\KycCase;
use App\Models\Organization;
use App\Support\TenantContext;
use Illuminate\Support\Str;

class KycKybAdapter extends IntegrationAdapter
{
    protected string $key = 'kyc_kyb';

    /**
     * Sandbox-Pruefung: liefert deterministisch "unauffaellig", ausser die Organisation
     * ist bereits als Risikoklasse "hoch" markiert (dann "auffaellig"). Ersetzt keinen
     * echten Anbieter, dient nur der Ablaufdemonstration.
     */
    public function screen(Organization $organization, ?int $reviewedBy = null): KycCase
    {
        $result = $organization->risk_class === 'hoch' ? 'auffaellig' : 'unauffaellig';
        $externalReference = 'KYB-SANDBOX-'.Str::upper(Str::random(8));

        $case = KycCase::create([
            'tenant_id' => TenantContext::id(),
            'organization_id' => $organization->id,
            'case_type' => 'KYB',
            'provider' => $this->provider()->name,
            'result' => $result,
            'risk_class' => $organization->risk_class,
            'reviewed_at' => now(),
            'next_review_at' => now()->addMonths($organization->risk_class === 'hoch' ? 6 : 12),
            'reviewed_by' => $reviewedBy,
            'is_demo' => $organization->is_demo,
        ]);

        $this->logSuccess(Organization::class, $organization->id, $externalReference, "KYB-Sandbox-Pruefung: {$result}");

        return $case;
    }
}
