<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\BeneficialOwner;
use App\Models\Contact;
use App\Models\Contract;
use App\Models\CreditLine;
use App\Models\CrmActivity;
use App\Models\DebtorLimit;
use App\Models\Decision;
use App\Models\DemoSeed;
use App\Models\Document;
use App\Models\DunningCase;
use App\Models\Facility;
use App\Models\FacilityEvent;
use App\Models\FactoringProduct;
use App\Models\FinancialScenario;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\KycCase;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\PayoutBatch;
use App\Models\Purchase;
use App\Models\Receivable;
use App\Models\Task;
use App\Models\Tenant;
use Database\Seeders\AureviaDemoDataSeeder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

/**
 * Loescht ausschliesslich Daten im Demo-Mandanten (is_demo = true auf dem Tenant).
 * Produktivmandanten sind technisch ausgeschlossen (siehe assertDemoTenant()).
 */
class DemoResetService
{
    /** Reihenfolge beachtet Fremdschluessel: Kinder vor Eltern. */
    private const MODELS_IN_DELETE_ORDER = [
        Payment::class, BankTransaction::class, Payout::class, PayoutBatch::class,
        JournalLine::class, JournalEntry::class, DunningCase::class, Purchase::class,
        Receivable::class, CreditLine::class, DebtorLimit::class, KycCase::class,
        BeneficialOwner::class, Contact::class, Contract::class, CrmActivity::class,
        Opportunity::class, Lead::class, Task::class, Document::class, FacilityEvent::class,
        Facility::class, BankAccount::class, FactoringProduct::class, Organization::class,
        FinancialScenario::class, Decision::class, DemoSeed::class,
    ];

    public function assertDemoTenant(Tenant $tenant): void
    {
        abort_unless($tenant->is_demo, 403, 'Löschung/Reset ist ausschließlich im Demo-Mandanten möglich. Produktivmandanten sind technisch ausgeschlossen.');
    }

    public function countDemoRecords(Tenant $tenant): int
    {
        return collect(self::MODELS_IN_DELETE_ORDER)
            ->sum(fn (string $model) => $model::where('tenant_id', $tenant->id)->count());
    }

    /**
     * Loescht alle Bewegungs- und Stammdaten des Demo-Mandanten. Gibt die Anzahl
     * betroffener Datensaetze zurueck. Organization nutzt SoftDeletes, wird hier
     * aber endgueltig entfernt (forceDelete), da es sich um reine Demo-Daten handelt.
     */
    public function wipe(Tenant $tenant): int
    {
        $this->assertDemoTenant($tenant);

        $affected = 0;

        DB::transaction(function () use ($tenant, &$affected) {
            foreach (self::MODELS_IN_DELETE_ORDER as $modelClass) {
                $query = $modelClass::where('tenant_id', $tenant->id);
                $affected += $query->count();

                if (in_array(SoftDeletes::class, class_uses_recursive($modelClass))) {
                    $modelClass::withTrashed()->where('tenant_id', $tenant->id)->forceDelete();
                } else {
                    $query->delete();
                }
            }
        });

        return $affected;
    }

    public function reseed(Tenant $tenant): void
    {
        app(AureviaDemoDataSeeder::class)->run();
    }
}
