<?php

namespace App\Services;

use App\Models\JournalEntry;
use App\Support\TenantContext;
use Illuminate\Support\Str;

/**
 * Unveraenderbares, doppisches Nebenbuch (Abschnitt 12). Buchungen ausschliesslich
 * additiv; Korrekturen erfolgen ueber reverse(), niemals durch Ueberschreiben.
 *
 * Kontenrahmen ist eine vereinfachte, konfigurierbare Demo-Zuordnung und ersetzt
 * keine steuerliche/handelsrechtliche Kontierung.
 */
class JournalService
{
    public const ACCOUNTS = [
        '1200' => 'Bankkonto',
        '1400' => 'Forderungen angekauft',
        '2100' => 'Verbindlichkeit gegenüber Kunde (Auszahlungsanspruch)',
        '2000' => 'Sicherheitseinbehalt / Reserve',
        '4000' => 'Factoring- und Zinsertrag',
        '2900' => 'Abzüge / Sonstige Verrechnung',
        '2500' => 'Verbindlichkeit gegenüber Investor (Fazilität)',
        '8000' => 'Kreditverlust (Ausfall)',
    ];

    /**
     * @param  array<int, array{account:string, debit?:float, credit?:float, organization_id?:int, contract_id?:int}>  $lines
     */
    public function post(string $eventType, array $lines, ?string $sourceType = null, ?int $sourceId = null, ?int $createdBy = null): JournalEntry
    {
        $totalDebit = round(array_sum(array_column($lines, 'debit')), 2);
        $totalCredit = round(array_sum(array_column($lines, 'credit')), 2);

        abort_unless($totalDebit === $totalCredit, 422, "Journalbuchung nicht ausgeglichen: Soll {$totalDebit} <> Haben {$totalCredit}");

        $entry = JournalEntry::create([
            'tenant_id' => TenantContext::id(),
            'entry_number' => 'JE-'.now()->format('Ymd').'-'.strtoupper(Str::random(6)),
            'booking_date' => now()->toDateString(),
            'value_date' => now()->toDateString(),
            'event_type' => $eventType,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'created_by' => $createdBy,
        ]);

        foreach ($lines as $line) {
            $entry->lines()->create([
                'tenant_id' => TenantContext::id(),
                'account_code' => $line['account'],
                'account_name' => self::ACCOUNTS[$line['account']] ?? $line['account'],
                'debit_amount' => $line['debit'] ?? 0,
                'credit_amount' => $line['credit'] ?? 0,
                'organization_id' => $line['organization_id'] ?? null,
                'contract_id' => $line['contract_id'] ?? null,
            ]);
        }

        return $entry;
    }

    public function reverse(JournalEntry $entry, ?int $createdBy = null): JournalEntry
    {
        $lines = $entry->lines->map(fn ($l) => [
            'account' => $l->account_code,
            'debit' => (float) $l->credit_amount,
            'credit' => (float) $l->debit_amount,
            'organization_id' => $l->organization_id,
            'contract_id' => $l->contract_id,
        ])->all();

        $reversal = $this->post('korrektur', $lines, $entry->source_type, $entry->source_id, $createdBy);
        $reversal->update(['reverses_entry_id' => $entry->id]);

        return $reversal;
    }
}
