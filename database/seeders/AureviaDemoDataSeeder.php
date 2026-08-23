<?php

namespace Database\Seeders;

use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\BeneficialOwner;
use App\Models\CapTableScenario;
use App\Models\Contact;
use App\Models\Contract;
use App\Models\CreditLine;
use App\Models\DebtorLimit;
use App\Models\Decision;
use App\Models\DemoSeed;
use App\Models\DunningCase;
use App\Models\EquityInstrument;
use App\Models\Facility;
use App\Models\FacilityEvent;
use App\Models\FactoringProduct;
use App\Models\FinancialScenario;
use App\Models\KycCase;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Organization;
use App\Models\OutsourcingRegistration;
use App\Models\Payout;
use App\Models\PayoutBatch;
use App\Models\ProjectRisk;
use App\Models\Purchase;
use App\Models\Receivable;
use App\Models\RelatedParty;
use App\Models\Shareholder;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Workstream;
use App\Services\JournalService;
use App\Services\PurchaseCalculator;
use App\Support\TenantContext;
use Illuminate\Database\Seeder;

/**
 * Erzeugt realistisch verknuepfte, ausschliesslich synthetische Demo-Daten
 * gemaess Abschnitt 21 des Masterprompts. Idempotent innerhalb einer
 * demo_seed_id: ein erneuter Lauf nach Reset erzeugt denselben Umfang neu.
 */
class AureviaDemoDataSeeder extends Seeder
{
    private const SPECIALTIES = [
        'Arztpraxis', 'Zahnarztpraxis', 'Apotheke', 'Tierklinik', 'Psychologische Praxis',
        'MVZ', 'Labor', 'Klinik', 'Arztpraxis', 'Zahnarztpraxis', 'Apotheke', 'MVZ',
    ];

    private const CITIES = ['Düsseldorf', 'Köln', 'Essen', 'Dortmund', 'Duisburg', 'Wuppertal', 'Bochum', 'Mönchengladbach'];

    private JournalService $journal;

    private PurchaseCalculator $calculator;

    private Tenant $tenant;

    private Contract $sampleContract;

    private array $customers = [];

    private array $debtors = [];

    private array $staffUserIds = [];

    public function run(): void
    {
        $this->journal = app(JournalService::class);
        $this->calculator = app(PurchaseCalculator::class);

        $this->tenant = Tenant::where('slug', 'aurevia-demo')->firstOrFail();
        TenantContext::set($this->tenant->id);

        if (DemoSeed::where('tenant_id', $this->tenant->id)->exists()) {
            return; // bereits befuellt; Reset/Loeschen erfolgt ueber DemoController
        }

        $this->staffUserIds = User::where('tenant_id', $this->tenant->id)->pluck('id')->all();

        $this->seedFinancialScenarios();
        $product = $this->seedFactoringProduct();
        $this->seedCustomers($product);
        $this->seedDebtors();
        $this->seedBankAccounts();
        $this->seedCreditLinesAndLimits();
        $this->seedReceivablesAndLifecycle();
        $this->seedCrmPipeline();
        $this->seedInvestorsAndFacilities();
        $this->seedGovernanceCockpit();
        $this->seedCapTableAndRegisters();

        DemoSeed::create([
            'tenant_id' => $this->tenant->id,
            'seed_id' => $this->tenant->demo_seed_id ?? 'seed-2026-08-23',
            'label' => 'Aurevia Kick-off-Demo v0.2',
        ]);
    }

    private function randomStaff(?int $exclude = null): int
    {
        $pool = array_values(array_diff($this->staffUserIds, array_filter([$exclude])));

        return $pool[array_rand($pool)];
    }

    private function seedFinancialScenarios(): void
    {
        $rows = [
            ['konservativ', 'Konservativ', 3000000, 40, 1.60, 50, 85, 0.25, 6.00, 9.50, 0.90],
            ['base', 'Base', 5000000, 60, 1.50, 45, 85, 0.15, 5.50, 9.00, 1.00],
            ['wachstum', 'Wachstum', 8000000, 80, 1.40, 40, 85, 0.15, 5.00, 8.50, 1.15],
            ['stress', 'Stress', 2500000, 20, 1.60, 60, 80, 0.60, 7.00, 10.00, 0.90],
        ];

        foreach ($rows as [$key, $label, $portfolio, $growth, $fee, $dso, $advance, $risk, $debtRate, $custRate, $opex]) {
            FinancialScenario::create([
                'tenant_id' => $this->tenant->id,
                'scenario_key' => $key,
                'label' => $label,
                'portfolio_year1_eur' => $portfolio,
                'growth_yoy_percent' => $growth,
                'factoring_fee_percent' => $fee,
                'dso_days' => $dso,
                'advance_rate_percent' => $advance,
                'risk_cost_percent' => $risk,
                'debt_interest_percent' => $debtRate,
                'customer_interest_percent' => $custRate,
                'opex_factor' => $opex,
                'source_document' => 'Finanzmodell V1 vom 19.08.2026 (Hypothese)',
                'status' => 'Hypothese',
                'is_demo' => true,
            ]);
        }
    }

    private function seedFactoringProduct(): FactoringProduct
    {
        return FactoringProduct::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Aurevia Full-Service Factoring Medizin',
            'recourse_type' => 'unecht_mit_regress',
            'service_type' => 'full_service',
            'disclosure_type' => 'offen',
            'scope_type' => 'gesamtumsatz',
            'active' => true,
        ]);
    }

    private function seedCustomers(FactoringProduct $product): void
    {
        $namesByType = [
            'Arztpraxis' => 'Praxis Dr.', 'Zahnarztpraxis' => 'Zahnarztpraxis Dr.', 'Apotheke' => 'Apotheke am',
            'Tierklinik' => 'Tierklinik', 'Psychologische Praxis' => 'Praxis für Psychotherapie',
            'MVZ' => 'MVZ Gesundheitszentrum', 'Labor' => 'Labor', 'Klinik' => 'Klinik',
        ];
        $surnames = ['Weber', 'Schneider', 'Hoffmann', 'Klein', 'Wolf', 'Neumann', 'Schwarz', 'Zimmermann', 'Braun', 'Krüger', 'Fischer', 'Becker'];

        foreach (self::SPECIALTIES as $i => $specialty) {
            $city = self::CITIES[$i % count(self::CITIES)];
            $name = ($namesByType[$specialty] ?? 'Praxis').' '.$surnames[$i];

            $org = Organization::create([
                'tenant_id' => $this->tenant->id,
                'org_type' => 'customer',
                'name' => $name,
                'legal_form' => 'Einzelpraxis',
                'specialty' => $specialty,
                'street' => 'Musterstraße '.random_int(1, 99),
                'zip' => random_int(40000, 47999),
                'city' => $city,
                'customer_status' => 'Aktiv',
                'risk_class' => $i % 7 === 0 ? 'hoch' : ($i % 3 === 0 ? 'mittel' : 'niedrig'),
                'is_demo' => true,
            ]);

            Contact::create([
                'tenant_id' => $this->tenant->id,
                'organization_id' => $org->id,
                'salutation' => $i % 2 === 0 ? 'Frau' : 'Herr',
                'first_name' => 'Alex',
                'last_name' => $surnames[$i],
                'role' => 'Inhaber:in',
                'email' => 'kontakt+demo'.$org->id.'@aurevia-factoring.de',
                'phone' => '0211 '.random_int(1000000, 9999999),
                'is_authorized_representative' => true,
                'is_demo' => true,
            ]);
            Contact::create([
                'tenant_id' => $this->tenant->id,
                'organization_id' => $org->id,
                'salutation' => 'Frau',
                'first_name' => 'Praxis',
                'last_name' => 'Management',
                'role' => 'Praxismanagement',
                'email' => 'pm+demo'.$org->id.'@aurevia-factoring.de',
                'is_demo' => true,
            ]);

            if ($i < 5) {
                BeneficialOwner::create([
                    'tenant_id' => $this->tenant->id,
                    'organization_id' => $org->id,
                    'first_name' => 'Alex',
                    'last_name' => $surnames[$i],
                    'ownership_percent' => 100,
                    'nationality' => 'DE',
                    'pep_status' => false,
                    'sanctions_hit' => false,
                    'screened_at' => now()->subDays(random_int(5, 90)),
                    'is_demo' => true,
                ]);
            }

            KycCase::create([
                'tenant_id' => $this->tenant->id,
                'organization_id' => $org->id,
                'case_type' => 'KYB',
                'provider' => 'Demo-Adapter',
                'result' => 'unauffaellig',
                'risk_class' => $org->risk_class,
                'reviewed_at' => now()->subDays(random_int(10, 120)),
                'next_review_at' => now()->addMonths($org->risk_class === 'hoch' ? 6 : 12),
                'reviewed_by' => $this->randomStaff(),
                'is_demo' => true,
            ]);

            $contract = Contract::create([
                'tenant_id' => $this->tenant->id,
                'organization_id' => $org->id,
                'factoring_product_id' => $product->id,
                'contract_number' => 'AUR-'.now()->format('y').'-'.str_pad($org->id, 4, '0', STR_PAD_LEFT),
                'start_date' => now()->subMonths(random_int(1, 10)),
                'term_months' => 24,
                'notice_period_days' => 90,
                'status' => 'aktiv',
                'purchase_line' => 150000 + $i * 25000,
                'payout_line' => 130000 + $i * 20000,
                'advance_rate_percent' => 85,
                'reserve_percent' => 15,
                'factoring_fee_percent' => 1.50,
                'reference_rate_percent' => 3.00,
                'margin_percent' => 2.50,
                'max_days_outstanding' => 120,
                'recourse_period_days' => 90,
                'day_count_convention' => 'act/360',
                'approved_by' => $this->randomStaff(),
                'approved_at' => now()->subMonths(random_int(1, 10)),
                'is_demo' => true,
            ]);

            $this->customers[] = ['org' => $org, 'contract' => $contract];
        }

        $this->sampleContract = $this->customers[0]['contract'];

        // Demo-Login-Nutzer an eine konkrete Praxis binden, damit das Kundendashboard Daten zeigt.
        User::where('email', 'demo.kunde_admin@aurevia-factoring.de')->update(['customer_org_id' => $this->customers[0]['org']->id]);
        User::where('email', 'demo.kunde_sachbearbeitung@aurevia-factoring.de')->update(['customer_org_id' => $this->customers[0]['org']->id]);
    }

    private function seedDebtors(): void
    {
        $companyPayers = ['Techniker Krankenkasse Regionaldirektion West', 'AOK Rheinland/Hamburg', 'Barmer Landesvertretung NRW', 'DAK-Gesundheit Regionaldirektion', 'IKK classic Regionaldirektion'];

        for ($i = 0; $i < 60; $i++) {
            $isPrivate = $i % 3 !== 0;
            $pseudonym = $isPrivate ? 'PAT-DEMO-'.str_pad((string) (100000 + $i), 6, '0', STR_PAD_LEFT) : null;

            $org = Organization::create([
                'tenant_id' => $this->tenant->id,
                'org_type' => 'debtor',
                'name' => $isPrivate ? $pseudonym : $companyPayers[$i % count($companyPayers)].' '.($i + 1),
                'city' => self::CITIES[$i % count(self::CITIES)],
                'risk_class' => $i % 11 === 0 ? 'hoch' : 'niedrig',
                'pseudonym_id' => $pseudonym,
                'is_demo' => true,
            ]);

            DebtorLimit::create([
                'tenant_id' => $this->tenant->id,
                'debtor_organization_id' => $org->id,
                'limit_amount' => $isPrivate ? 15000 : 500000,
                'status' => 'aktiv',
                'valid_until' => now()->addYear(),
                'is_demo' => true,
            ]);

            $this->debtors[] = $org;
        }
    }

    private function seedBankAccounts(): void
    {
        BankAccount::create([
            'tenant_id' => $this->tenant->id,
            'account_name' => 'Betriebskonto Aurevia',
            'bank_name' => 'Medizinbank AG – Demo (fiktiv)',
            'iban_masked' => 'DE00 DEMO 0000 0000 01',
            'currency' => 'EUR',
            'balance_amount' => 250000,
            'purpose' => 'betrieb',
            'is_demo' => true,
        ]);

        BankAccount::create([
            'tenant_id' => $this->tenant->id,
            'account_name' => 'Auszahlungskonto Aurevia',
            'bank_name' => 'Medizinbank AG – Demo (fiktiv)',
            'iban_masked' => 'DE00 DEMO 0000 0000 02',
            'currency' => 'EUR',
            'balance_amount' => 400000,
            'purpose' => 'auszahlung',
            'is_demo' => true,
        ]);
    }

    private function seedCreditLinesAndLimits(): void
    {
        foreach ($this->customers as $c) {
            CreditLine::create([
                'tenant_id' => $this->tenant->id,
                'organization_id' => $c['org']->id,
                'contract_id' => $c['contract']->id,
                'line_type' => 'ankauf',
                'limit_amount' => $c['contract']->purchase_line,
                'used_amount' => 0,
                'status' => 'aktiv',
                'valid_from' => now()->subMonths(1),
                'valid_until' => now()->addYear(),
                'decided_by' => $this->randomStaff(),
                'is_demo' => true,
            ]);
            CreditLine::create([
                'tenant_id' => $this->tenant->id,
                'organization_id' => $c['org']->id,
                'contract_id' => $c['contract']->id,
                'line_type' => 'auszahlung',
                'limit_amount' => $c['contract']->payout_line,
                'used_amount' => 0,
                'status' => 'aktiv',
                'valid_from' => now()->subMonths(1),
                'valid_until' => now()->addYear(),
                'decided_by' => $this->randomStaff(),
                'is_demo' => true,
            ]);
        }
    }

    /**
     * @return array{status:string, count:int}[]
     */
    private function statusPlan(): array
    {
        return [
            ['status' => 'entwurf', 'count' => 7],
            ['status' => 'eingereicht', 'count' => 20],
            ['status' => 'formale_pruefung', 'count' => 15],
            ['status' => 'rueckfrage', 'count' => 10],
            ['status' => 'freigegeben', 'count' => 15],
            ['status' => 'angekauft', 'count' => 40],
            ['status' => 'zur_auszahlung', 'count' => 10],
            ['status' => 'zahlung_angewiesen', 'count' => 10],
            ['status' => 'ausgezahlt', 'count' => 60],
            ['status' => 'teilbezahlt', 'count' => 10],
            ['status' => 'bezahlt', 'count' => 20],
            ['status' => 'abgerechnet', 'count' => 15],
            ['status' => 'abgelehnt', 'count' => 10],
            ['status' => 'zurueckgezogen', 'count' => 5],
            ['status' => 'gesperrt', 'count' => 3],
            ['status' => 'streitig', 'count' => 5],
            ['status' => 'ueberfaellig', 'count' => 15],
            ['status' => 'rueckgriff', 'count' => 3],
            ['status' => 'ausgefallen', 'count' => 3],
            ['status' => 'abgeschrieben', 'count' => 2],
            ['status' => 'wieder_eingezogen', 'count' => 2],
        ];
    }

    private function seedReceivablesAndLifecycle(): void
    {
        $counter = 0;
        $postFreigabeStatuses = ['angekauft', 'zur_auszahlung', 'zahlung_angewiesen', 'ausgezahlt', 'teilbezahlt', 'bezahlt', 'abgerechnet', 'ueberfaellig', 'rueckgriff', 'ausgefallen', 'abgeschrieben', 'wieder_eingezogen'];

        $payoutQueue = []; // ['purchase' => Purchase, 'targetStatus' => string]

        foreach ($this->statusPlan() as $plan) {
            for ($i = 0; $i < $plan['count']; $i++) {
                $counter++;
                $customer = $this->customers[$counter % count($this->customers)];
                $debtor = $this->debtors[$counter % count($this->debtors)];

                $invoiceDate = now()->copy()->subDays(random_int(5, 150));
                $dueDate = $invoiceDate->copy()->addDays(30);
                $amount = round(random_int(80000, 950000) / 100, 2);

                $receivable = Receivable::create([
                    'tenant_id' => $this->tenant->id,
                    'receivable_number' => 'FRD-DEMO-'.str_pad((string) $counter, 6, '0', STR_PAD_LEFT),
                    'organization_id' => $customer['org']->id,
                    'contract_id' => $customer['contract']->id,
                    'debtor_organization_id' => $debtor->id,
                    'debtor_pseudonym_id' => $debtor->pseudonym_id,
                    'invoice_number' => 'RG-'.$invoiceDate->format('ymd').'-'.$counter,
                    'invoice_date' => $invoiceDate->toDateString(),
                    'due_date' => $dueDate->toDateString(),
                    'invoice_amount' => $amount,
                    'status' => $plan['status'],
                    'source_channel' => 'manuell',
                    'submitted_by' => $this->randomStaff(),
                    'reviewed_by' => in_array($plan['status'], ['entwurf', 'eingereicht']) ? null : $this->randomStaff(),
                    'rejection_reason' => $plan['status'] === 'abgelehnt' ? 'Rechnung dupliziert bzw. Limit überschritten (Demo-Grund).' : null,
                    'triggered_rule' => $plan['status'] === 'abgelehnt' ? 'duplikat' : null,
                    'is_demo' => true,
                ]);

                if (in_array($plan['status'], $postFreigabeStatuses, true)) {
                    $purchase = $this->calculator->calculate($receivable);
                    $firstApprover = $this->randomStaff();
                    $secondApprover = $this->randomStaff($firstApprover);
                    $purchase->update([
                        'status' => 'freigegeben',
                        'approved_by_first' => $firstApprover,
                        'approved_by_second' => $secondApprover,
                        'purchased_at' => $invoiceDate->copy()->addDays(2),
                    ]);

                    $this->journal->post('ankauf', [
                        ['account' => '1400', 'debit' => (float) $purchase->nominal_amount, 'organization_id' => $customer['org']->id, 'contract_id' => $customer['contract']->id],
                        ['account' => '2100', 'credit' => (float) $purchase->immediate_payout_amount, 'organization_id' => $customer['org']->id],
                        ['account' => '2000', 'credit' => (float) $purchase->reserve_amount, 'organization_id' => $customer['org']->id],
                        ['account' => '4000', 'credit' => round((float) $purchase->factoring_fee_amount + (float) $purchase->expected_interest_amount, 2), 'organization_id' => $customer['org']->id],
                    ], Purchase::class, $purchase->id, $secondApprover);

                    CreditLine::where('organization_id', $customer['org']->id)->where('line_type', 'ankauf')->increment('used_amount', $purchase->nominal_amount);
                    CreditLine::where('organization_id', $customer['org']->id)->where('line_type', 'auszahlung')->increment('used_amount', $purchase->immediate_payout_amount);
                    DebtorLimit::where('debtor_organization_id', $debtor->id)->increment('used_amount', $purchase->nominal_amount);

                    if ($plan['status'] !== 'angekauft') {
                        $payoutQueue[] = ['purchase' => $purchase, 'receivable' => $receivable, 'targetStatus' => $plan['status']];
                    }
                }

                if ($plan['status'] === 'streitig') {
                    DunningCase::create([
                        'tenant_id' => $this->tenant->id, 'receivable_id' => $receivable->id, 'case_type' => 'streitfall',
                        'dunning_level' => 1, 'status' => 'in_klaerung', 'reason' => 'Leistungsumfang wird vom Debitor bestritten (Demo).',
                        'open_amount' => $amount, 'assignee_id' => $this->randomStaff(), 'next_action_date' => now()->addDays(5), 'is_demo' => true,
                    ]);
                }
                if ($plan['status'] === 'rueckgriff') {
                    DunningCase::create([
                        'tenant_id' => $this->tenant->id, 'receivable_id' => $receivable->id, 'case_type' => 'rueckgriff',
                        'dunning_level' => 2, 'status' => 'offen', 'reason' => 'Debitor zahlungsunfähig, Rückgriff auf Kunde (Demo).',
                        'open_amount' => $amount, 'assignee_id' => $this->randomStaff(), 'next_action_date' => now()->addDays(3), 'is_demo' => true,
                    ]);
                }
                if ($plan['status'] === 'ausgefallen') {
                    DunningCase::create([
                        'tenant_id' => $this->tenant->id, 'receivable_id' => $receivable->id, 'case_type' => 'ausfall',
                        'dunning_level' => 3, 'status' => 'offen', 'reason' => 'Forderung als ausgefallen eingestuft (Demo).',
                        'open_amount' => $amount, 'assignee_id' => $this->randomStaff(), 'next_action_date' => now()->addDays(10), 'is_demo' => true,
                    ]);
                }
                if (in_array($plan['status'], ['ueberfaellig']) && $counter % 4 === 0) {
                    DunningCase::create([
                        'tenant_id' => $this->tenant->id, 'receivable_id' => $receivable->id, 'case_type' => 'mahnung',
                        'dunning_level' => 1, 'status' => 'offen', 'reason' => 'Zahlungsziel überschritten (Demo).',
                        'open_amount' => $amount, 'assignee_id' => $this->randomStaff(), 'next_action_date' => now()->addDays(7), 'is_demo' => true,
                    ]);
                }

                if (in_array($plan['status'], ['bezahlt', 'abgerechnet', 'teilbezahlt', 'wieder_eingezogen'])) {
                    $payFactor = $plan['status'] === 'teilbezahlt' ? 0.5 : 1.0;
                    $payment = $receivable->payments()->create([
                        'tenant_id' => $this->tenant->id,
                        'amount' => round($amount * $payFactor, 2),
                        'type' => $plan['status'] === 'teilbezahlt' ? 'teilzahlung' : 'eingang',
                        'match_confidence_percent' => 95,
                        'match_reason' => 'Demo-Zahlungseingang automatisch zugeordnet',
                        'matched_by' => $this->randomStaff(),
                        'matched_at' => $dueDate->copy()->addDays(random_int(1, 10)),
                        'is_demo' => true,
                    ]);

                    $this->journal->post('zahlungseingang', [
                        ['account' => '1200', 'debit' => (float) $payment->amount, 'organization_id' => $customer['org']->id],
                        ['account' => '1400', 'credit' => (float) $payment->amount, 'organization_id' => $customer['org']->id],
                    ], Receivable::class, $receivable->id, $payment->matched_by);

                    if ($plan['status'] === 'abgerechnet' && $receivable->purchase && (float) $receivable->purchase->reserve_amount > 0) {
                        $this->journal->post('reservefreigabe', [
                            ['account' => '2000', 'debit' => (float) $receivable->purchase->reserve_amount, 'organization_id' => $customer['org']->id],
                            ['account' => '2100', 'credit' => (float) $receivable->purchase->reserve_amount, 'organization_id' => $customer['org']->id],
                        ], Receivable::class, $receivable->id, $payment->matched_by);
                    }
                }
            }
        }

        $this->seedPayoutBatches($payoutQueue);

        // Ein paar offene, unzugeordnete Kontobewegungen fuer die Zahlungszuordnung-Demo.
        $betrieb = BankAccount::where('purpose', 'betrieb')->first();
        Receivable::where('status', 'ausgezahlt')->inRandomOrder()->limit(6)->get()->each(function (Receivable $r) use ($betrieb) {
            $tolerance = $r->invoice_number[-1] === '3' ? -1.5 : 0; // gelegentliche Toleranzabweichung fuer die Matching-Demo
            BankTransaction::create([
                'tenant_id' => $this->tenant->id,
                'bank_account_id' => $betrieb->id,
                'value_date' => now()->subDays(random_int(0, 3)),
                'amount' => round((float) $r->invoice_amount + $tolerance, 2),
                'reference' => 'RG '.$r->invoice_number.' '.$r->receivable_number,
                'counterparty_name' => $r->debtorOrganization->name ?? $r->debtor_pseudonym_id,
                'import_source' => 'camt.053',
                'status' => 'offen',
                'is_demo' => true,
            ]);
        });
        // Eine unbekannte Zahlung ohne passende Forderung.
        BankTransaction::create([
            'tenant_id' => $this->tenant->id,
            'bank_account_id' => $betrieb->id,
            'value_date' => now()->subDay(),
            'amount' => 733.10,
            'reference' => 'Unklare Gutschrift ohne Referenz (Demo)',
            'counterparty_name' => 'Unbekannt',
            'import_source' => 'camt.053',
            'status' => 'offen',
            'is_demo' => true,
        ]);
    }

    private function seedPayoutBatches(array $payoutQueue): void
    {
        $auszahlungskonto = BankAccount::where('purpose', 'auszahlung')->first();
        $chunks = array_chunk($payoutQueue, (int) ceil(count($payoutQueue) / 8) ?: 1);

        foreach ($chunks as $index => $chunk) {
            if (empty($chunk)) {
                continue;
            }

            $targetStatus = $chunk[0]['targetStatus'];
            $batchStatus = match (true) {
                $targetStatus === 'zur_auszahlung' => 'freigegeben_1',
                $targetStatus === 'zahlung_angewiesen' => 'angewiesen',
                default => 'bestaetigt',
            };

            $batch = PayoutBatch::create([
                'tenant_id' => $this->tenant->id,
                'batch_number' => 'BATCH-DEMO-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                'bank_account_id' => $auszahlungskonto->id,
                'total_amount' => collect($chunk)->sum(fn ($item) => (float) $item['purchase']->immediate_payout_amount),
                'item_count' => count($chunk),
                'status' => $batchStatus,
                'approved_by_first' => $this->randomStaff(),
                'approved_by_second' => $batchStatus !== 'freigegeben_1' ? $this->randomStaff() : null,
                'sepa_export_reference' => $batchStatus !== 'freigegeben_1' ? 'sepa/demo-seed-batch-'.($index + 1).'.xml' : null,
                'executed_at' => $batchStatus !== 'freigegeben_1' ? now()->subDays(random_int(1, 20)) : null,
                'is_demo' => true,
            ]);

            foreach ($chunk as $item) {
                $payoutStatus = match ($batchStatus) {
                    'bestaetigt' => 'bestaetigt',
                    'angewiesen' => 'angewiesen',
                    default => 'erstellt',
                };

                $payout = Payout::create([
                    'tenant_id' => $this->tenant->id,
                    'payout_batch_id' => $batch->id,
                    'purchase_id' => $item['purchase']->id,
                    'organization_id' => $item['receivable']->organization_id,
                    'amount' => $item['purchase']->immediate_payout_amount,
                    'idempotency_key' => 'PAYOUT-DEMO-'.$item['purchase']->id,
                    'status' => $payoutStatus,
                    'confirmed_at' => $payoutStatus === 'bestaetigt' ? now()->subDays(random_int(1, 15)) : null,
                    'is_demo' => true,
                ]);

                if ($payoutStatus === 'bestaetigt') {
                    $this->journal->post('auszahlung', [
                        ['account' => '2100', 'debit' => (float) $payout->amount, 'organization_id' => $payout->organization_id],
                        ['account' => '1200', 'credit' => (float) $payout->amount, 'organization_id' => $payout->organization_id],
                    ], Payout::class, $payout->id, $batch->approved_by_second ?? $batch->approved_by_first);
                }
            }
        }
    }

    private function seedCrmPipeline(): void
    {
        $names = ['Praxis Dr. Lehmann', 'Zahnarztpraxis Dr. Vogel', 'Apotheke am Markt', 'Tierarztpraxis Dr. Sommer', 'MVZ Rheinblick', 'Labor Nordrhein', 'Praxis Dr. Keller', 'Physiozentrum Rhein', 'Klinik am Park', 'Praxisgemeinschaft Altstadt'];

        foreach ($names as $i => $name) {
            $lead = Lead::create([
                'tenant_id' => $this->tenant->id,
                'company_name' => $name,
                'specialty' => self::SPECIALTIES[$i % count(self::SPECIALTIES)],
                'contact_name' => 'Ansprechpartner '.($i + 1),
                'contact_email' => 'lead'.$i.'@beispiel-demo.de',
                'source' => $i % 2 === 0 ? 'Empfehlung' : 'Website',
                'status' => Lead::STATUSES[$i % (count(Lead::STATUSES) - 1)],
                'owner_id' => $this->randomStaff(),
                'is_demo' => true,
            ]);

            if ($i < 5) {
                Opportunity::create([
                    'tenant_id' => $this->tenant->id,
                    'lead_id' => $lead->id,
                    'name' => 'Factoring '.$name,
                    'expected_volume' => 200000 + $i * 50000,
                    'probability_percent' => 30 + $i * 10,
                    'stage' => Opportunity::STAGES[$i % (count(Opportunity::STAGES) - 1)],
                    'expected_close_date' => now()->addDays(30 + $i * 10),
                    'next_action' => 'Angebotstermin vereinbaren',
                    'owner_id' => $this->randomStaff(),
                    'is_demo' => true,
                ]);
            }
        }
    }

    private function seedInvestorsAndFacilities(): void
    {
        $investorNames = ['Medizinbank AG – Demo (fiktiv)', 'Rheinland Finanzierungspartner – Demo (fiktiv)'];

        foreach ($investorNames as $i => $name) {
            $investorOrg = Organization::create([
                'tenant_id' => $this->tenant->id,
                'org_type' => 'investor',
                'name' => $name,
                'city' => 'Düsseldorf',
                'is_demo' => true,
            ]);

            $facility = Facility::create([
                'tenant_id' => $this->tenant->id,
                'investor_organization_id' => $investorOrg->id,
                'facility_number' => 'FAC-DEMO-'.($i + 1),
                'name' => 'Revolvierende Fazilität '.($i + 1),
                'commitment_amount' => $i === 0 ? 5000000 : 3000000,
                'drawn_amount' => $i === 0 ? 3200000 : 900000,
                'interest_rate_percent' => $i === 0 ? 5.5 : 6.0,
                'commitment_fee_percent' => 0.5,
                'start_date' => now()->subMonths(6),
                'maturity_date' => now()->addYears(3),
                'seniority' => 'senior',
                'covenants' => ['max_leverage' => 3.5, 'min_liquidity_eur' => 200000],
                'status' => 'aktiv',
                'detail_level' => 'aggregiert',
                'is_demo' => true,
            ]);

            FacilityEvent::create([
                'tenant_id' => $this->tenant->id, 'facility_id' => $facility->id, 'event_type' => 'drawdown',
                'amount' => $facility->drawn_amount, 'event_date' => now()->subMonths(2), 'covenant_status' => 'eingehalten', 'is_demo' => true,
            ]);
            $this->journal->post('investorenziehung', [
                ['account' => '1200', 'debit' => (float) $facility->drawn_amount],
                ['account' => '2500', 'credit' => (float) $facility->drawn_amount],
            ], Facility::class, $facility->id, $this->randomStaff());

            FacilityEvent::create([
                'tenant_id' => $this->tenant->id, 'facility_id' => $facility->id, 'event_type' => 'zinszahlung',
                'amount' => round($facility->drawn_amount * $facility->interest_rate_percent / 100 / 12, 2),
                'event_date' => now()->subDays(15), 'covenant_status' => 'eingehalten', 'is_demo' => true,
            ]);

            if ($i === 0) {
                FacilityEvent::create([
                    'tenant_id' => $this->tenant->id, 'facility_id' => $facility->id, 'event_type' => 'covenant_check',
                    'event_date' => now()->subDays(5), 'covenant_status' => 'warnung',
                    'notes' => 'Konzentration Top-10 nähert sich der vereinbarten Schwelle (Demo-Warnung).', 'is_demo' => true,
                ]);
            }
        }
    }

    /**
     * Projekt-, Governance- und Annahmencockpit (Abschnitt 14.1): Workstreams A-J,
     * Risk Log und ein erstes Decision Log. Alle Eintraege sind Hypothese/Entwurf
     * und duerfen extern nicht angezeigt werden (Zugriff bereits auf Geschaeftsleitung/
     * Superadmin beschraenkt, siehe routes/aurevia.php).
     */
    private function seedGovernanceCockpit(): void
    {
        $plan = [
            ['A', 'Gesellschaft & Recht', 'geplant', 21],
            ['B', 'Kapital & Finanzierung', 'in_arbeit', 14],
            ['C', 'BaFin-Erlaubnis & Regulatorik', 'in_arbeit', 45],
            ['D', 'Compliance, KYC/KYB & Datenschutz', 'blockiert', 30],
            ['E', 'Produkt & Konditionen', 'in_arbeit', 20],
            ['F', 'Technologie & Systeme (Intranet/CRM)', 'in_arbeit', 3],
            ['G', 'Treasury & Bankanbindung', 'geplant', 60],
            ['H', 'Vertrieb & Markt', 'geplant', 25],
            ['I', 'Organisation & Personal', 'geplant', 40],
            ['J', 'Investor Relations & Reporting', 'geplant', 35],
        ];

        $workstreams = [];
        foreach ($plan as [$code, $title, $status, $daysUntilDue]) {
            $workstreams[$code] = Workstream::create([
                'tenant_id' => $this->tenant->id,
                'code' => $code,
                'title' => $title,
                'owner_id' => $this->randomStaff(),
                'deputy_id' => $this->randomStaff(),
                'deliverables' => 'Siehe Kickoff-Paper und Workstream-Charter (Entwurf).',
                'due_date' => now()->addDays($daysUntilDue),
                'status' => $status,
                'is_demo' => true,
            ]);
        }

        $risks = [
            ['workstream' => 'C', 'title' => 'BaFin-Erlaubnisverfahren dauert laenger als geplant', 'probability' => 'mittel', 'impact' => 'hoch', 'mitigation' => 'Fruehzeitige Vorabstimmung mit Kanzlei und BaFin, Pufferzeit im Zeitplan.'],
            ['workstream' => 'B', 'title' => 'Eigenkapital reicht bei Base-Case-Anlaufverlusten nicht bis Break-even', 'probability' => 'mittel', 'impact' => 'hoch', 'mitigation' => 'Nachschusshypothese pruefen, Kostenkurve im Konservativ-Szenario gegensteuern.'],
            ['workstream' => 'D', 'title' => 'DSGVO-Rechtsgrundlage fuer Gesundheitsdaten noch nicht extern bestaetigt', 'probability' => 'mittel', 'impact' => 'hoch', 'mitigation' => 'Rechtsgutachten beauftragen, Medical Data Firewall technisch bereits vorbereitet.'],
            ['workstream' => 'G', 'title' => 'Kernbank fuer EBICS/PSD2 noch nicht ausgewaehlt', 'probability' => 'hoch', 'impact' => 'mittel', 'mitigation' => 'Provider-Adapter statt Festverdrahtung, RFP an mehrere Banken.'],
            ['workstream' => 'H', 'title' => 'Abhaengigkeit von wenigen Empfehlungsgebern im Neugeschaeft', 'probability' => 'niedrig', 'impact' => 'mittel', 'mitigation' => 'Kanalmix in der CRM-Pipeline aktiv diversifizieren.'],
        ];

        foreach ($risks as $r) {
            ProjectRisk::create([
                'tenant_id' => $this->tenant->id,
                'workstream_id' => $workstreams[$r['workstream']]->id,
                'title' => $r['title'],
                'probability' => $r['probability'],
                'impact' => $r['impact'],
                'mitigation' => $r['mitigation'],
                'owner_id' => $this->randomStaff(),
                'due_date' => now()->addDays(30),
                'status' => 'offen',
                'is_demo' => true,
            ]);
        }

        $decisions = [
            ['DEC-2026-001', 'Arbeitsname und Claim fuer die Demo festlegen', 'Beschlossen', -20],
            ['DEC-2026-002', 'Zielrechtsform AG oder SE', 'In Pruefung', -10],
            ['DEC-2026-003', 'Kernbank fuer Kontenmodell und EBICS', 'Externer Rat erforderlich', -5],
            ['DEC-2026-004', 'Full-Service vs. reine Forderungsfinanzierung als Phase-1-Scope', 'Beschlossen', -15],
        ];

        foreach ($decisions as [$id, $title, $status, $daysAgo]) {
            Decision::create([
                'tenant_id' => $this->tenant->id,
                'decision_id' => $id,
                'title' => $title,
                'status' => $status,
                'decision_date' => now()->addDays($daysAgo),
                'participants' => 'Gruendungsteam (Vorschlagsdatensaetze, siehe Personenliste)',
                'owner' => 'Timo Müller',
                'is_demo' => true,
            ]);
        }
    }

    /**
     * Cap-Table-Hypothese, Related-Party-Register und Auslagerungsregister
     * (Abschnitt 14.1/19). Streng geschuetztes Modul, alle Werte Entwurf/Hypothese.
     */
    private function seedCapTableAndRegisters(): void
    {
        $scenario = CapTableScenario::create([
            'tenant_id' => $this->tenant->id,
            'scenario_key' => 'gruendung_v0_1',
            'label' => 'Gründungsszenario v0.1',
            'status' => 'Hypothese',
            'description' => 'Vorlaeufige Kapitalverteilung gemaess Founder-Term-Sheet-Geruest (Entwurf).',
            'is_demo' => true,
        ]);

        $founders = [
            ['Timo Müller', 40], ['David Enns', 15], ['Jürgen Brink', 15], ['Carsten Walprecht', 15], ['Jan Walprecht', 15],
        ];

        foreach ($founders as [$name, $percent]) {
            $shareholder = Shareholder::create([
                'tenant_id' => $this->tenant->id, 'name' => $name, 'type' => 'person',
                'notes' => 'Vorschlagsdatensatz Gruendungsteam, keine beschlossene Beteiligung.', 'is_demo' => true,
            ]);

            EquityInstrument::create([
                'tenant_id' => $this->tenant->id,
                'shareholder_id' => $shareholder->id,
                'cap_table_scenario_id' => $scenario->id,
                'instrument_type' => 'anteile',
                'percentage' => $percent,
                'valid_from' => now()->toDateString(),
                'status' => 'Hypothese',
                'is_demo' => true,
            ]);
        }

        RelatedParty::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Carsten Walprecht',
            'relation_type' => 'gesellschafter',
            'description' => 'Mögliche Doppelrolle als Vertriebspartner und Gesellschafter (Demo-Hinweis).',
            'conflict_status' => 'zu_pruefen',
            'mitigation' => 'Interessenkonfliktprüfung im Rahmen von Workstream A vorgesehen.',
            'is_demo' => true,
        ]);

        $outsourcing = [
            ['Hosting/Webspace', 'PHP-Webspace-Provider (Demo)', 'finanzdaten', 'hoch', true],
            ['KYC/KYB-Pruefung', 'Sandbox-Adapter (Demo)', 'personenbezogen', 'hoch', true],
            ['Buchhaltung/DATEV', 'Steuerberatung (Demo)', 'finanzdaten', 'mittel', false],
        ];

        foreach ($outsourcing as [$service, $provider, $access, $criticality, $dora]) {
            OutsourcingRegistration::create([
                'tenant_id' => $this->tenant->id,
                'service' => $service,
                'provider' => $provider,
                'data_access' => $access,
                'criticality' => $criticality,
                'contract_reference' => 'Entwurf, noch nicht unterzeichnet',
                'exit_plan' => 'Exit-Plan noch zu erstellen (offener Punkt).',
                'audit_right' => false,
                'dora_relevant' => $dora,
                'is_demo' => true,
            ]);
        }
    }
}
