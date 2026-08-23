<?php

namespace App\Services\Integrations;

use App\Models\Organization;
use Illuminate\Support\Str;

class CreditBureauAdapter extends IntegrationAdapter
{
    protected string $key = 'credit_bureau';

    /**
     * Sandbox-Bonitaetsauskunft: heuristischer Demo-Score aus der Risikoklasse,
     * kein Anschluss an eine echte Wirtschaftsauskunftei.
     *
     * @return array{score:int, rating:string}
     */
    public function score(Organization $organization): array
    {
        $result = match ($organization->risk_class) {
            'hoch' => ['score' => 45, 'rating' => 'C'],
            'mittel' => ['score' => 68, 'rating' => 'B'],
            default => ['score' => 82, 'rating' => 'A'],
        };

        $this->logSuccess(
            Organization::class,
            $organization->id,
            'CB-SANDBOX-'.Str::upper(Str::random(8)),
            "Bonitaetsauskunft (Sandbox): Score {$result['score']}, Rating {$result['rating']}"
        );

        return $result;
    }
}
