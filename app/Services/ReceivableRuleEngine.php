<?php

namespace App\Services;

use App\Models\CreditLine;
use App\Models\DebtorLimit;
use App\Models\Receivable;

/**
 * Regelwerk pro Forderung (Abschnitt 10.3). Jede Ablehnung/Rueckfrage liefert
 * einen konkreten, dem Nutzer erklaerbaren Grund plus die ausgeloeste Regel.
 */
class ReceivableRuleEngine
{
    /**
     * @return array{passed: bool, reason: ?string, rule: ?string}
     */
    public function evaluate(Receivable $receivable): array
    {
        $contract = $receivable->contract;
        $organization = $receivable->organization;

        if (! $contract || ! $contract->isActive()) {
            return $this->fail('vertrag_inaktiv', 'Der zugeordnete Factoringvertrag ist nicht aktiv.');
        }

        if ($organization && $organization->customer_status !== 'Aktiv' && $organization->customer_status !== 'aktiv') {
            // Onboarding-Status ist informativ, blockiert im Prototyp aber nicht zwingend.
        }

        $duplicate = Receivable::where('id', '!=', $receivable->id)
            ->where('organization_id', $receivable->organization_id)
            ->where('invoice_number', $receivable->invoice_number)
            ->whereNotIn('status', ['abgelehnt', 'zurueckgezogen'])
            ->exists();

        if ($duplicate) {
            return $this->fail('duplikat', "Rechnungsnummer {$receivable->invoice_number} wurde für diesen Kunden bereits eingereicht.");
        }

        if ((float) $receivable->invoice_amount <= 0) {
            return $this->fail('betrag_ungueltig', 'Der Rechnungsbetrag muss größer als 0 sein.');
        }

        $ageDays = now()->diffInDays($receivable->invoice_date);
        if ($ageDays > $contract->max_days_outstanding) {
            return $this->fail('alter_ueberschritten', "Die Rechnung ist älter als die zulässigen {$contract->max_days_outstanding} Tage (Maximum Days Outstanding).");
        }

        $purchaseLine = CreditLine::where('organization_id', $receivable->organization_id)
            ->where('line_type', 'ankauf')->where('status', 'aktiv')->first();

        if ($purchaseLine && $purchaseLine->availableAmount() < (float) $receivable->invoice_amount * ($contract->advance_rate_percent / 100)) {
            return $this->fail('kundenlimit_ueberschritten', 'Die verfügbare Ankaufslinie des Kunden reicht für diese Forderung nicht aus.');
        }

        if ($receivable->debtor_organization_id) {
            $debtorLimit = DebtorLimit::where('debtor_organization_id', $receivable->debtor_organization_id)->first();
            if ($debtorLimit && $debtorLimit->status === 'aktiv') {
                $available = (float) $debtorLimit->limit_amount - (float) $debtorLimit->used_amount;
                if ($available < (float) $receivable->invoice_amount) {
                    return $this->fail('debitorenlimit_ueberschritten', 'Das Limit für diesen Debitor bzw. diese Debitorengruppe reicht nicht aus.');
                }
            }
        }

        return ['passed' => true, 'reason' => null, 'rule' => null];
    }

    private function fail(string $rule, string $reason): array
    {
        return ['passed' => false, 'reason' => $reason, 'rule' => $rule];
    }
}
