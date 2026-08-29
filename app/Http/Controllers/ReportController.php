<?php

namespace App\Http\Controllers;

use App\Mail\KpiReportMail;
use App\Models\JournalLine;
use App\Models\Receivable;
use App\Models\ReportSubscription;
use App\Services\Integrations\DatevExportAdapter;
use App\Services\KpiReportBuilder;
use App\Support\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class ReportController extends Controller
{
    public function index()
    {
        $subscriptions = ReportSubscription::with('creator')->latest('id')->get();

        return view('reports.index', compact('subscriptions'));
    }

    /**
     * KPI-Report sofort per E-Mail versenden (v3.00, manueller Versand).
     */
    public function sendKpiReport(Request $request, KpiReportBuilder $builder)
    {
        $data = $request->validate(['recipient_email' => 'required|email:strict']);

        try {
            Mail::to($data['recipient_email'])
                ->send(new KpiReportMail($builder->build(), now()->format('d.m.Y H:i')));
        } catch (TransportExceptionInterface $e) {
            report($e);

            return back()->withErrors(['recipient_email' => __('E-Mail-Versand fehlgeschlagen — SMTP-Zugangsdaten in der .env prüfen.')]);
        }

        AuditLogger::log('export', ReportSubscription::class, null, [], [], 'KPI-Report manuell versendet an '.$data['recipient_email']);

        return back()->with('status', __('KPI-Report versendet an :email.', ['email' => $data['recipient_email']]));
    }

    /**
     * Automatischen Report einrichten (taeglich/woechentlich/monatlich).
     */
    public function storeSubscription(Request $request)
    {
        $data = $request->validate([
            'recipient_email' => 'required|email:strict',
            'frequency' => 'required|in:taeglich,woechentlich,monatlich',
        ]);

        ReportSubscription::create($data + [
            'tenant_id' => TenantContext::id(),
            'report_type' => 'kpi_uebersicht',
            'created_by' => $request->user()->id,
        ]);

        return back()->with('status', __('Automatischer Report eingerichtet (:frequency).', ['frequency' => $data['frequency']]));
    }

    public function toggleSubscription(Request $request, ReportSubscription $subscription)
    {
        $subscription->update(['active' => ! $subscription->active]);

        return back()->with('status', $subscription->active ? __('Report aktiviert.') : __('Report pausiert.'));
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
