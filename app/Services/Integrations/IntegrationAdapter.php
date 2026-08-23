<?php

namespace App\Services\Integrations;

use App\Models\IntegrationEvent;
use App\Models\IntegrationProvider;
use App\Support\TenantContext;

/**
 * Basisklasse fuer austauschbare Provider-Adapter (Abschnitt 20). Jeder Adapter
 * protokolliert seine Aufrufe im Statusregister (integration_providers) und im
 * Ereignisprotokoll (integration_events), unabhaengig vom konkreten Anbieter.
 * Deaktivierung eines Adapters (active=false) darf nie Daten loeschen.
 */
abstract class IntegrationAdapter
{
    /** Eindeutiger Schluessel aus IntegrationCatalog::PROVIDERS. */
    protected string $key;

    public function provider(): IntegrationProvider
    {
        $meta = IntegrationCatalog::PROVIDERS[$this->key];

        return IntegrationProvider::firstOrCreate(
            ['tenant_id' => TenantContext::id(), 'key' => $this->key],
            ['category' => $meta['category'], 'name' => $meta['name'], 'mode' => 'sandbox', 'status' => 'unbekannt']
        );
    }

    protected function logSuccess(?string $subjectType = null, ?int $subjectId = null, ?string $externalReference = null, ?string $summary = null, ?string $consentReference = null): IntegrationEvent
    {
        $provider = $this->provider();
        $provider->update(['status' => 'healthy', 'last_success_at' => now()]);

        return $this->log('erfolgreich', $subjectType, $subjectId, $externalReference, $summary, $consentReference);
    }

    protected function logFailure(?string $subjectType = null, ?int $subjectId = null, ?string $summary = null): IntegrationEvent
    {
        $this->provider()->update(['status' => 'fehler']);

        return $this->log('fehlgeschlagen', $subjectType, $subjectId, null, $summary);
    }

    private function log(string $status, ?string $subjectType, ?int $subjectId, ?string $externalReference, ?string $summary, ?string $consentReference = null): IntegrationEvent
    {
        return IntegrationEvent::create([
            'tenant_id' => TenantContext::id(),
            'integration_provider_id' => $this->provider()->id,
            'direction' => 'outbound',
            'external_reference' => $externalReference,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'status' => $status,
            'consent_reference' => $consentReference,
            'summary' => $summary,
            'created_at' => now(),
        ]);
    }
}
