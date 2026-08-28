<?php

namespace App\Services\Integrations;

use App\Models\CreditLine;

/**
 * Vorbereiteter Adapter fuer die Warenkreditversicherung (v3.00, Sandbox).
 *
 * Zielprozess (mit dem Versicherer zu verhandeln): monatliche Meldung aller
 * versicherten bzw. zu versichernden Kreditlinien (Betrag, internes Rating,
 * Debitor pseudonymisiert), Rueckmeldung von Annahme/Ablehnung/Praemie.
 * Bis zur echten Anbindung protokolliert der Adapter nur das Ereignis.
 */
class CreditInsuranceAdapter extends IntegrationAdapter
{
    protected string $key = 'credit_insurance';

    /**
     * Monatliche Linienmeldung (Demo): zaehlt meldepflichtige Linien oberhalb
     * der Klumpenrisiko-Schwelle und protokolliert die Meldung.
     */
    public function reportLines(): array
    {
        $threshold = (float) config('aurevia.insurance_threshold');

        $reportable = CreditLine::where('status', 'aktiv')
            ->where('limit_amount', '>', $threshold)
            ->get(['id', 'limit_amount', 'insured_amount', 'insurance_status']);

        $uninsured = $reportable->where('insurance_status', 'nicht_versichert');

        $this->logSuccess(CreditLine::class, null, null, sprintf(
            'Monatsmeldung (Demo): %d Linien über %.0f EUR, davon %d ohne Versicherungsschutz.',
            $reportable->count(), $threshold, $uninsured->count()
        ));

        return [
            'reportable' => $reportable->count(),
            'uninsured' => $uninsured->count(),
        ];
    }
}
