<?php

namespace App\Console\Commands;

use App\Models\Receivable;
use App\Support\AuditLogger;
use Illuminate\Console\Command;

/**
 * Setzt faellige Forderungen auf 'ueberfaellig' (taeglich per Scheduler).
 * Ohne dieses Kommando wuerde der Status in Produktion nie vergeben und
 * Mahnliste sowie Overdue-Kennzahlen blieben dauerhaft leer.
 */
class MarkOverdueCommand extends Command
{
    protected $signature = 'aurevia:mark-overdue';

    protected $description = 'Markiert faellige Forderungen mit offenem Zahlungsanspruch als ueberfaellig';

    /**
     * Nur Status mit echtem offenem Zahlungsanspruch werden ueberschrieben;
     * Workflow-Zustaende (entwurf, eingereicht, streitig, ...) bleiben unberuehrt.
     */
    private const ELIGIBLE_STATUSES = ['zur_auszahlung', 'zahlung_angewiesen', 'ausgezahlt', 'teilbezahlt'];

    public function handle(): int
    {
        $count = 0;

        Receivable::query()
            ->whereIn('status', self::ELIGIBLE_STATUSES)
            ->whereDate('due_date', '<', now()->toDateString())
            ->orderBy('id')
            ->chunkById(200, function ($receivables) use (&$count) {
                foreach ($receivables as $receivable) {
                    $old = $receivable->status;
                    $receivable->update(['status' => 'ueberfaellig']);
                    AuditLogger::log('update', Receivable::class, $receivable->id,
                        ['status' => $old], ['status' => 'ueberfaellig'], 'Automatisch: Faelligkeit ueberschritten');
                    $count++;
                }
            });

        $this->info("{$count} Forderung(en) als überfällig markiert.");

        return self::SUCCESS;
    }
}
