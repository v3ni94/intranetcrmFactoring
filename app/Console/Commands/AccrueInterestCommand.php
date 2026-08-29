<?php

namespace App\Console\Commands;

use App\Models\Facility;
use App\Models\FacilityEvent;
use Illuminate\Console\Command;

/**
 * v3.03: Schreibt die monatliche Zinsausschuettung je aktiver Fazilitaet fort
 * (nachschuessig fuer den Vormonat), sofern fuer den Vormonat noch kein
 * Zinszahlungs-Ereignis existiert. So laufen die Ausschuettungsdaten auch in
 * Zukunft automatisch weiter. Laeuft monatlich ueber den Scheduler.
 */
class AccrueInterestCommand extends Command
{
    protected $signature = 'aurevia:accrue-interest';

    protected $description = 'Monatliche Zinsausschüttungen je aktiver Fazilität fortschreiben';

    public function handle(): int
    {
        $previousMonth = now()->subMonthNoOverflow();
        $created = 0;

        foreach (Facility::where('status', 'aktiv')->get() as $facility) {
            if ((float) $facility->drawn_amount <= 0) {
                continue;
            }
            if ($facility->start_date && $previousMonth->endOfMonth()->lt($facility->start_date)) {
                continue;
            }

            $exists = FacilityEvent::where('facility_id', $facility->id)
                ->where('event_type', 'zinszahlung')
                ->whereBetween('event_date', [now()->startOfMonth(), now()->endOfMonth()])
                ->exists();

            if ($exists) {
                continue;
            }

            FacilityEvent::create([
                'tenant_id' => $facility->tenant_id,
                'facility_id' => $facility->id,
                'event_type' => 'zinszahlung',
                'amount' => round((float) $facility->drawn_amount * (float) $facility->interest_rate_percent / 100 / 12, 2),
                'event_date' => now()->startOfMonth()->addDays(2),
                'covenant_status' => 'eingehalten',
                'notes' => 'Monatliche Ausschüttung '.$previousMonth->format('m/Y').' (automatisch fortgeschrieben)',
                'is_demo' => (bool) $facility->is_demo,
            ]);
            $created++;
        }

        $this->info("Zinsausschüttungen fortgeschrieben: {$created}.");

        return self::SUCCESS;
    }
}
