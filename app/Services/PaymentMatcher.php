<?php

namespace App\Services;

use App\Models\BankTransaction;
use App\Models\Receivable;
use Illuminate\Support\Collection;

/**
 * Schlaegt eine Zuordnung offener Kontobewegungen zu offenen Forderungen vor
 * (Abschnitt 11.3). Rein regelbasiert, keine automatische Buchung ohne Bestaetigung.
 */
class PaymentMatcher
{
    public const OPEN_RECEIVABLE_STATUSES = ['zur_auszahlung', 'zahlung_angewiesen', 'ausgezahlt', 'teilbezahlt', 'ueberfaellig'];

    /**
     * Offene Forderungen einmal laden und bei mehreren suggest()-Aufrufen
     * wiederverwenden (sonst ein Full-Table-Load pro Kontobewegung).
     */
    public function openReceivables(): Collection
    {
        return Receivable::whereIn('status', self::OPEN_RECEIVABLE_STATUSES)->get();
    }

    /**
     * @return array{receivable: ?Receivable, confidence: float, reason: string}
     */
    public function suggest(BankTransaction $transaction, ?Collection $candidates = null): array
    {
        $candidates ??= $this->openReceivables();

        $exactAmount = $candidates->first(fn (Receivable $r) => (float) $r->invoice_amount === round((float) $transaction->amount, 2));
        if ($exactAmount) {
            return ['receivable' => $exactAmount, 'confidence' => 95.0, 'reason' => 'Exakte Betragsübereinstimmung'];
        }

        if ($transaction->reference) {
            $byReference = $candidates->first(fn (Receivable $r) => str_contains($transaction->reference, $r->receivable_number) || str_contains($transaction->reference, $r->invoice_number));
            if ($byReference) {
                return ['receivable' => $byReference, 'confidence' => 90.0, 'reason' => 'Verwendungszweck enthält Rechnungs-/Forderungsnummer'];
            }
        }

        $tolerance = $candidates->first(function (Receivable $r) use ($transaction) {
            $diff = abs((float) $r->invoice_amount - (float) $transaction->amount);

            return $diff > 0 && $diff <= 2.00;
        });
        if ($tolerance) {
            return ['receivable' => $tolerance, 'confidence' => 60.0, 'reason' => 'Betrag innerhalb Toleranz (± 2,00 EUR), z.B. Skonto/Rundung'];
        }

        return ['receivable' => null, 'confidence' => 0.0, 'reason' => 'Keine passende offene Forderung gefunden'];
    }

    /**
     * Erzeugt synthetische Kontoauszugsbewegungen fuer die Demo (camt.053-Ersatz).
     */
    public function generateDemoStatement(int $bankAccountId, Collection $receivables): array
    {
        $created = [];

        foreach ($receivables as $receivable) {
            $created[] = BankTransaction::create([
                'bank_account_id' => $bankAccountId,
                'value_date' => now()->toDateString(),
                'amount' => $receivable->invoice_amount,
                'reference' => 'RG '.$receivable->invoice_number.' '.$receivable->receivable_number,
                'counterparty_name' => optional($receivable->debtorOrganization)->name ?? $receivable->debtor_pseudonym_id ?? 'Unbekannter Zahler',
                'import_source' => 'camt.053',
                'status' => 'offen',
                'is_demo' => true,
            ]);
        }

        return $created;
    }
}
