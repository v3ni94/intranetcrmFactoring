<?php

namespace App\Http\Controllers;

use App\Models\JournalLine;
use App\Models\Receivable;
use App\Services\Integrations\DatevExportAdapter;
use App\Support\AuditLogger;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index()
    {
        return view('reports.index');
    }

    public function exportReceivables(): StreamedResponse
    {
        AuditLogger::log('export', Receivable::class, null, [], [], 'CSV-Export Forderungen');

        return $this->csv('forderungen.csv', ['Nummer', 'Kunde', 'Rechnungsnummer', 'Betrag', 'Status', 'Fällig'], function () {
            return Receivable::with('organization')->cursor()->map(fn (Receivable $r) => [
                $r->receivable_number, $r->organization->name ?? '', $r->invoice_number,
                number_format((float) $r->invoice_amount, 2, ',', '.'), $r->statusLabel(), $r->due_date?->format('d.m.Y'),
            ]);
        });
    }

    public function exportJournal(): StreamedResponse
    {
        AuditLogger::log('export', JournalLine::class, null, [], [], 'CSV-Export Journal');

        return $this->csv('journal.csv', ['Buchungsnr.', 'Datum', 'Ereignis', 'Konto', 'Soll', 'Haben'], function () {
            return JournalLine::with('entry')->cursor()->map(fn (JournalLine $l) => [
                $l->entry->entry_number ?? '', $l->entry?->booking_date?->format('d.m.Y'), $l->entry->event_type ?? '',
                $l->account_code.' '.$l->account_name, number_format((float) $l->debit_amount, 2, ',', '.'), number_format((float) $l->credit_amount, 2, ',', '.'),
            ]);
        });
    }

    public function exportDatev(DatevExportAdapter $adapter): StreamedResponse
    {
        $export = $adapter->exportBookings();
        AuditLogger::log('export', JournalLine::class, null, [], [], 'DATEV-Demo-Export');

        return $this->csv('datev_buchungsstapel_demo.csv', $export['header'], fn () => $export['rows']);
    }

    private function csv(string $filename, array $header, callable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($header, $rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $header, ';');
            foreach ($rows() as $row) {
                fputcsv($handle, $row, ';');
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
