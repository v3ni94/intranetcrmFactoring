<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * KPI-Report per E-Mail (v3.00): kompakte Kennzahlenuebersicht, manuell oder
 * per Abonnement (taeglich/woechentlich/monatlich) versendet.
 */
class KpiReportMail extends Mailable
{
    /**
     * @param  array<string, string>  $kpis  Label => formatierter Wert
     */
    public function __construct(public array $kpis, public string $reportDate) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Aurevia KPI-Report vom :date', ['date' => $this->reportDate]),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'mail.kpi-report');
    }
}
