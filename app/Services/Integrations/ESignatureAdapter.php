<?php

namespace App\Services\Integrations;

use App\Models\Contract;
use App\Models\Document;
use App\Support\TenantContext;
use Illuminate\Support\Str;

class ESignatureAdapter extends IntegrationAdapter
{
    protected string $key = 'esignature';

    /**
     * Demo-Signatur: erzeugt einen unveraenderlichen Dokumenteneintrag mit
     * Signaturvermerk. Kein Anschluss an einen echten E-Signatur-Anbieter.
     */
    public function sign(Contract $contract, int $signedBy): Document
    {
        $reference = 'SIG-DEMO-'.Str::upper(Str::random(10));

        $document = Document::create([
            'tenant_id' => TenantContext::id(),
            'title' => "Vertrag {$contract->contract_number} – signiert (Demo)",
            'category' => 'vertrag',
            'related_type' => Contract::class,
            'related_id' => $contract->id,
            'visibility' => 'intern',
            'export_locked' => true,
            'owner_id' => $signedBy,
            'is_demo' => $contract->is_demo,
        ]);

        $this->logSuccess(Contract::class, $contract->id, $reference, 'Vertrag digital signiert (Demo-Signatur, kein Rechtsverkehr)');

        return $document;
    }
}
