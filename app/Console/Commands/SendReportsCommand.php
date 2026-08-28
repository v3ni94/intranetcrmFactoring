<?php

namespace App\Console\Commands;

use App\Mail\KpiReportMail;
use App\Models\ReportSubscription;
use App\Services\KpiReportBuilder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Versendet faellige Report-Abonnements (v3.00). Laeuft taeglich per Scheduler;
 * die Faelligkeit je Abo (taeglich/woechentlich/monatlich) prueft das Modell.
 */
class SendReportsCommand extends Command
{
    protected $signature = 'aurevia:send-reports';

    protected $description = 'Versendet faellige KPI-Report-Abonnements per E-Mail';

    public function handle(KpiReportBuilder $builder): int
    {
        $due = ReportSubscription::where('active', true)->get()->filter->isDue();

        if ($due->isEmpty()) {
            $this->info('Keine fälligen Report-Abonnements.');

            return self::SUCCESS;
        }

        $kpis = $builder->build();
        $sent = 0;

        foreach ($due as $subscription) {
            try {
                Mail::to($subscription->recipient_email)
                    ->send(new KpiReportMail($kpis, now()->format('d.m.Y H:i')));
                $subscription->update(['last_sent_at' => now()]);
                $sent++;
            } catch (\Throwable $e) {
                report($e);
                $this->warn("Versand an {$subscription->recipient_email} fehlgeschlagen: {$e->getMessage()}");
            }
        }

        $this->info("{$sent} Report(s) versendet.");

        return self::SUCCESS;
    }
}
