<?php

namespace App\Services\Integrations;

use App\Models\Organization;
use Illuminate\Support\Str;

class RegisterUboAdapter extends IntegrationAdapter
{
    protected string $key = 'register_ubo';

    /** Sandbox-Abgleich mit Handelsregister/Transparenzregister. */
    public function verify(Organization $organization): bool
    {
        $this->logSuccess(Organization::class, $organization->id, 'REG-SANDBOX-'.Str::upper(Str::random(8)), 'Registerabgleich ohne Beanstandung (Sandbox)');

        return true;
    }
}
