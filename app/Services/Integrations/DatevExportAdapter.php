<?php

namespace App\Services\Integrations;

use App\Models\JournalLine;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Str;

class DatevExportAdapter extends IntegrationAdapter
{
    protected string $key = 'datev';

    /**
     * Erzeugt eine DATEV-nahe CSV (Buchungsstapel-Demo) ueber ein konfigurierbares
     * Sachkontenmapping. Ersetzt keine echte DATEV-Schnittstelle.
     *
     * @return array{header: array<int,string>, rows: LazyCollection}
     */
    public function exportBookings()
    {
        $reference = 'DATEV-DEMO-'.Str::upper(Str::random(8));

        $this->logSuccess(JournalLine::class, null, $reference, 'DATEV-Demo-Export erzeugt');

        return [
            'header' => ['Belegdatum', 'Buchungstext', 'Konto', 'Soll', 'Haben', 'Buchungsnummer'],
            'rows' => JournalLine::with('entry')->cursor()->map(fn (JournalLine $l) => [
                $l->entry?->booking_date?->format('d.m.Y'),
                $l->entry->event_type ?? '',
                $l->account_code,
                number_format((float) $l->debit_amount, 2, ',', '.'),
                number_format((float) $l->credit_amount, 2, ',', '.'),
                $l->entry->entry_number ?? '',
            ]),
        ];
    }
}
