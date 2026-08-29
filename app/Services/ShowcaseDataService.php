<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\Contact;
use App\Models\Contract;
use App\Models\CreditLine;
use App\Models\DebtorLimit;
use App\Models\DunningCase;
use App\Models\Facility;
use App\Models\FacilityEvent;
use App\Models\FactoringProduct;
use App\Models\KycCase;
use App\Models\Lead;
use App\Models\OperatingCost;
use App\Models\Opportunity;
use App\Models\Organization;
use App\Models\Purchase;
use App\Models\Receivable;
use App\Models\Tenant;
use App\Models\Ticket;
use App\Models\User;
use App\Support\RatingCatalog;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Testdaten fuer Vorfuehrzwecke (v3.03): realistisch wirkende, aber vollstaendig
 * FIKTIVE Kunden, Investoren, Vertraege, Forderungen, Ausschuettungen und Kosten
 * ueber die Jahre 2025/2026 — auf JEDEM Mandanten einspielbar, ausnahmslos mit
 * is_demo = true markiert und darueber rueckstandslos loeschbar.
 *
 * Bewusst KEINE echten Firmennamen als Kunden: gegenueber Banken/BaFin/Anwaelten
 * waeren echte Namen als angebliche Kunden irrefuehrend. Die Investoren-Testdaten
 * tragen den Zusatz "(Testdatensatz)".
 */
class ShowcaseDataService
{
    private const SURNAMES = [
        'Weber', 'Schneider', 'Hoffmann', 'Klein', 'Wolf', 'Neumann', 'Schwarz', 'Zimmermann',
        'Braun', 'Krüger', 'Fischer', 'Becker', 'Lorenz', 'Seidel', 'Brandt', 'Haas', 'Kraus',
        'Engel', 'Horn', 'Busch', 'Bergmann', 'Pohl', 'Winkler', 'Voigt', 'Sauer', 'Arnold',
        'Stein', 'Otto', 'Groß', 'Albrecht', 'Wenzel', 'Lindner', 'Ebert', 'Franke', 'Marquardt',
        'Hummel', 'Wilms', 'Janssen', 'Rothe', 'Kastner',
    ];

    private const CITIES = [
        'Düsseldorf', 'Köln', 'Essen', 'Dortmund', 'Hamburg', 'München', 'Berlin', 'Frankfurt am Main',
        'Stuttgart', 'Leipzig', 'Dresden', 'Hannover', 'Nürnberg', 'Bremen', 'Münster', 'Aachen',
        'Bonn', 'Wiesbaden', 'Mainz', 'Karlsruhe', 'Freiburg', 'Augsburg', 'Kiel', 'Rostock',
        'Erfurt', 'Würzburg', 'Regensburg', 'Osnabrück', 'Kassel', 'Ulm',
    ];

    /** Segment => [Anzahl, Namensmuster, Rechtsform] */
    private const SEGMENT_PLAN = [
        'arzt' => [24, 'Praxis Dr. med. %s', 'Einzelpraxis'],
        'zahnarzt' => [14, 'Zahnarztpraxis Dr. %s & Kollegen', 'GbR'],
        'apotheke' => [12, '%s-Apotheke %s', 'e. K.'],
        'dentallabor' => [8, 'Dentallabor %s GmbH', 'GmbH'],
        'tierarzt' => [8, 'Tierärztliche Praxis Dr. %s', 'Einzelpraxis'],
        'heilberufe' => [10, 'Therapiezentrum %s', 'GmbH'],
        'pflege' => [6, 'Pflegedienst %s GmbH', 'GmbH'],
        'mvz_klinik' => [10, 'MVZ Gesundheitszentrum %s GmbH', 'GmbH'],
        'sonstige' => [8, 'Klinik am %s gGmbH (fiktiv)', 'gGmbH'],
    ];

    private const APOTHEKEN_PRAEFIXE = ['Rats', 'Löwen', 'Stern', 'Rosen', 'Linden', 'Sonnen', 'Adler', 'Markt', 'Park', 'Stadt', 'Hirsch', 'Glocken'];

    private const KLINIK_ORTE = ['Stadtpark', 'Rosengarten', 'Lindenhof', 'Königsweg', 'Marienplatz', 'Südring', 'Nordufer', 'Schlossberg'];

    private Tenant $tenant;

    private array $staffIds = [];

    private array $customers = [];

    private array $debtors = [];

    public function __construct(
        private JournalService $journal,
        private PurchaseCalculator $calculator,
        private ContractTemplateService $contracts,
    ) {}

    public function hasShowcaseData(Tenant $tenant): bool
    {
        return Organization::where('tenant_id', $tenant->id)->where('is_demo', true)->exists();
    }

    public function countTestRecords(Tenant $tenant): int
    {
        return collect(DemoResetService::MODELS_IN_DELETE_ORDER)
            ->sum(fn (string $model) => $model::where('tenant_id', $tenant->id)->where('is_demo', true)->count());
    }

    public function countAllRecords(Tenant $tenant): int
    {
        return collect(DemoResetService::MODELS_IN_DELETE_ORDER)
            ->sum(fn (string $model) => $model::where('tenant_id', $tenant->id)->count());
    }

    /** Loescht ausschliesslich Datensaetze mit is_demo = true. */
    public function purgeTestData(Tenant $tenant): int
    {
        return $this->purge($tenant, onlyDemo: true);
    }

    /** Loescht ALLE Bewegungs-/Stammdaten des Mandanten (Nutzer, Rollen, Tenant bleiben). */
    public function purgeAll(Tenant $tenant): int
    {
        return $this->purge($tenant, onlyDemo: false);
    }

    private function purge(Tenant $tenant, bool $onlyDemo): int
    {
        $affected = 0;

        DB::transaction(function () use ($tenant, $onlyDemo, &$affected) {
            foreach (DemoResetService::MODELS_IN_DELETE_ORDER as $modelClass) {
                $hasDemoFlag = Schema::hasColumn((new $modelClass)->getTable(), 'is_demo');
                $usesSoftDeletes = in_array(SoftDeletes::class, class_uses_recursive($modelClass));

                // Ohne is_demo-Spalte (z. B. Journalzeilen) haengen die Zeilen an
                // demo-markierten Eltern und fallen ueber deren Loeschung bzw. den
                // Vollmodus weg; im Nur-Demo-Modus werden sie ueber die Elternkette geloescht.
                // Soft-geloeschte Zeilen ausdruecklich einbeziehen, damit auch bereits
                // "geloeschte" Testdatensaetze rueckstandslos verschwinden.
                $query = $usesSoftDeletes
                    ? $modelClass::withTrashed()->where('tenant_id', $tenant->id)
                    : $modelClass::where('tenant_id', $tenant->id);
                if ($onlyDemo) {
                    if (! $hasDemoFlag) {
                        continue;
                    }
                    $query->where('is_demo', true);
                }

                $affected += $query->count();

                if ($usesSoftDeletes) {
                    $query->forceDelete();
                } else {
                    $query->delete();
                }
            }

            if ($onlyDemo) {
                // Testdaten-Produkt hat keine is_demo-Spalte, ist aber am Namen erkennbar
                $affected += FactoringProduct::where('tenant_id', $tenant->id)
                    ->where('name', 'like', '%(Testdaten)%')->delete();
            }
        });

        return $affected;
    }

    /** Spielt den kompletten Vorfuehr-Datensatz ein. Idempotent je Mandant. */
    public function seed(Tenant $tenant, User $actor): int
    {
        if ($this->hasShowcaseData($tenant)) {
            return 0;
        }

        @set_time_limit(0);

        $this->tenant = $tenant;
        TenantContext::set($tenant->id);

        $this->staffIds = User::where('tenant_id', $tenant->id)->pluck('id')->all() ?: [$actor->id];

        mt_srand(20260829); // reproduzierbarer Datensatz

        // Atomar: schlaegt ein Schritt fehl (z. B. Unique-Kollision), bleibt kein
        // halber Datensatz zurueck — sonst waere erneutes Einspielen blockiert,
        // obwohl die Daten unvollstaendig sind.
        DB::transaction(function () use ($actor) {
            $product = $this->seedProduct();
            $this->seedBankAccounts();
            $this->seedCustomers($product);
            $this->seedDebtors();
            $this->seedReceivables();
            $this->seedInvestors($actor);
            $this->seedCosts($actor);
            $this->seedCrm();
            $this->seedTickets($actor);
            $this->seedContractDocuments($actor);
        });

        return $this->countTestRecords($tenant);
    }

    private function staff(?int $exclude = null): int
    {
        $pool = array_values(array_diff($this->staffIds, array_filter([$exclude])));

        return $pool !== [] ? $pool[array_rand($pool)] : $this->staffIds[0];
    }

    private function seedProduct(): FactoringProduct
    {
        return FactoringProduct::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Aurevia Full-Service Factoring Medizin (Testdaten)',
            'recourse_type' => 'unecht_mit_regress',
            'service_type' => 'full_service',
            'disclosure_type' => 'offen',
            'scope_type' => 'gesamtumsatz',
            'active' => true,
        ]);
    }

    private function seedBankAccounts(): void
    {
        $accounts = [
            ['Betriebskonto', 'betrieb', 350000, '01'],
            ['Auszahlungskonto', 'auszahlung', 520000, '02'],
            ['Abwicklungskonto Kundengelder (Treuhand)', 'abwicklung_kunden', 180000, '03'],
            ['Abwicklungskonto Investorengelder', 'abwicklung_investoren', 940000, '04'],
        ];

        foreach ($accounts as [$name, $purpose, $balance, $suffix]) {
            BankAccount::create([
                'tenant_id' => $this->tenant->id,
                'account_name' => $name.' Aurevia',
                'bank_name' => 'Testbank AG (fiktiv)',
                'iban_masked' => 'DE00 TEST 0000 0000 '.$suffix,
                'currency' => 'EUR',
                'balance_amount' => $balance,
                'purpose' => $purpose,
                'is_demo' => true,
            ]);
        }
    }

    private function seedCustomers(FactoringProduct $product): void
    {
        $ratingPool = array_merge(
            array_fill(0, 8, 'AAA'), array_fill(0, 15, 'AA'), array_fill(0, 25, 'A'),
            array_fill(0, 25, 'BBB'), array_fill(0, 15, 'BB'), array_fill(0, 8, 'B'),
            array_fill(0, 3, 'CCC'), array_fill(0, 1, 'C'),
        );

        $i = 0;
        foreach (self::SEGMENT_PLAN as $segment => [$count, $pattern, $legalForm]) {
            for ($n = 0; $n < $count; $n++, $i++) {
                $surname = self::SURNAMES[$i % count(self::SURNAMES)];
                $city = self::CITIES[$i % count(self::CITIES)];

                $name = match ($segment) {
                    'apotheke' => sprintf($pattern, self::APOTHEKEN_PRAEFIXE[$n % count(self::APOTHEKEN_PRAEFIXE)], $city),
                    'sonstige' => sprintf($pattern, self::KLINIK_ORTE[$n % count(self::KLINIK_ORTE)]),
                    'heilberufe' => sprintf($pattern, $city),
                    'mvz_klinik' => sprintf($pattern, $city),
                    'pflege', 'dentallabor' => sprintf($pattern, $surname),
                    default => sprintf($pattern, $surname),
                };

                $rating = $ratingPool[$i % count($ratingPool)];
                $points = RatingCatalog::GRADES[$rating]['min_points'] + mt_rand(0, 8);
                $isLarge = in_array($segment, ['mvz_klinik', 'sonstige'], true);

                $org = Organization::create([
                    'tenant_id' => $this->tenant->id,
                    'org_type' => 'customer',
                    'name' => $name,
                    'legal_form' => $legalForm,
                    'specialty' => RatingCatalog::SEGMENTS[$segment],
                    'segment' => $segment,
                    'customer_type' => in_array($segment, ['apotheke', 'dentallabor', 'pflege'], true) ? 'b2b' : (mt_rand(0, 2) === 0 ? 'b2c' : 'b2b'),
                    'street' => 'Beispielweg '.mt_rand(1, 120),
                    'zip' => str_pad((string) mt_rand(10000, 99999), 5, '0', STR_PAD_LEFT),
                    'city' => $city,
                    'customer_status' => 'Aktiv',
                    'risk_class' => in_array($rating, ['B', 'CCC', 'C'], true) ? 'hoch' : (in_array($rating, ['BBB', 'BB'], true) ? 'mittel' : 'niedrig'),
                    'rating' => $rating,
                    'rating_points' => min(100, $points),
                    'rating_updated_at' => Carbon::create(2026, mt_rand(1, 8), mt_rand(1, 28)),
                    'is_demo' => true,
                ]);

                Contact::create([
                    'tenant_id' => $this->tenant->id,
                    'organization_id' => $org->id,
                    'salutation' => $i % 2 === 0 ? 'Frau' : 'Herr',
                    'first_name' => $i % 2 === 0 ? 'Julia' : 'Martin',
                    'last_name' => $surname,
                    'role' => 'Inhaber:in / Geschäftsführung',
                    'email' => 'kontakt+test'.$i.'@example.com',
                    'phone' => '030 '.mt_rand(1000000, 9999999),
                    'is_authorized_representative' => true,
                    'is_demo' => true,
                ]);

                KycCase::create([
                    'tenant_id' => $this->tenant->id,
                    'organization_id' => $org->id,
                    'case_type' => 'KYB',
                    'provider' => 'Sandbox-Adapter (Testdaten)',
                    'result' => 'unauffaellig',
                    'risk_class' => $org->risk_class,
                    'reviewed_at' => Carbon::create(2025, mt_rand(1, 12), mt_rand(1, 28)),
                    'next_review_at' => now()->addMonths($org->risk_class === 'hoch' ? 6 : 12),
                    'reviewed_by' => $this->staff(),
                    'is_demo' => true,
                ]);

                $start = Carbon::create(2025, 1, 1)->addDays(mt_rand(0, 500))->startOfDay();
                $purchaseLine = $isLarge ? mt_rand(400000, 1500000) : mt_rand(80000, 350000);

                $contract = Contract::create([
                    'tenant_id' => $this->tenant->id,
                    'organization_id' => $org->id,
                    'factoring_product_id' => $product->id,
                    // Mandanten-Kennung in der Nummer: contract_number ist global unique,
                    // sonst kollidiert das Einspielen auf einem zweiten Mandanten.
                    'contract_number' => 'AUR-'.$start->format('y').'-T'.$this->tenant->id.'-'.str_pad((string) (1000 + $i), 4, '0', STR_PAD_LEFT),
                    'start_date' => $start,
                    'term_months' => 24,
                    'notice_period_days' => 90,
                    'status' => 'aktiv',
                    'purchase_line' => $purchaseLine,
                    'payout_line' => (int) round($purchaseLine * 0.87),
                    'advance_rate_percent' => [80, 85, 85, 88, 90][mt_rand(0, 4)],
                    'reserve_percent' => [20, 15, 15, 12, 10][mt_rand(0, 4)],
                    'factoring_fee_percent' => round(mt_rand(120, 220) / 100, 2),
                    'reference_rate_percent' => 3.00,
                    'margin_percent' => round(mt_rand(180, 320) / 100, 2),
                    'max_days_outstanding' => 120,
                    'recourse_period_days' => 90,
                    'day_count_convention' => 'act/360',
                    'approved_by' => $this->staff(),
                    'approved_at' => $start,
                    'is_demo' => true,
                ]);

                foreach (['ankauf' => $contract->purchase_line, 'auszahlung' => $contract->payout_line] as $type => $limit) {
                    CreditLine::create([
                        'tenant_id' => $this->tenant->id,
                        'organization_id' => $org->id,
                        'contract_id' => $contract->id,
                        'line_type' => $type,
                        'limit_amount' => $limit,
                        'used_amount' => 0,
                        'status' => 'aktiv',
                        'valid_from' => $start,
                        'valid_until' => $start->copy()->addYears(2),
                        'decided_by' => $this->staff(),
                        'insurer_name' => $limit > config('aurevia.insurance_threshold', 30000) ? 'Kreditversicherer AG (fiktiv)' : null,
                        'insured_amount' => $limit > config('aurevia.insurance_threshold', 30000) ? (int) round($limit * 0.8) : null,
                        'insurance_status' => $limit > config('aurevia.insurance_threshold', 30000) ? 'versichert' : 'nicht_versichert',
                        'is_demo' => true,
                    ]);
                }

                $this->customers[] = ['org' => $org, 'contract' => $contract];
            }
        }
    }

    private function seedDebtors(): void
    {
        $payers = [
            'Gesetzliche Krankenkasse Nord (Testdatensatz)', 'Gesetzliche Krankenkasse West (Testdatensatz)',
            'Private Krankenversicherung Süd AG (Testdatensatz)', 'Beihilfestelle Land (Testdatensatz)',
            'Kassenzahnärztliche Verrechnungsstelle (Testdatensatz)', 'Berufsgenossenschaft Gesundheit (Testdatensatz)',
        ];

        for ($i = 0; $i < 120; $i++) {
            $isPrivate = $i % 3 !== 0;
            $pseudonym = $isPrivate ? 'PAT-TEST-'.str_pad((string) (200000 + $i), 6, '0', STR_PAD_LEFT) : null;

            $org = Organization::create([
                'tenant_id' => $this->tenant->id,
                'org_type' => 'debtor',
                'name' => $isPrivate ? $pseudonym : $payers[$i % count($payers)].' '.(intdiv($i, count($payers)) + 1),
                'city' => self::CITIES[$i % count(self::CITIES)],
                'risk_class' => $i % 13 === 0 ? 'hoch' : 'niedrig',
                'pseudonym_id' => $pseudonym,
                'is_demo' => true,
            ]);

            DebtorLimit::create([
                'tenant_id' => $this->tenant->id,
                'debtor_organization_id' => $org->id,
                'limit_amount' => $isPrivate ? 15000 : 750000,
                'status' => 'aktiv',
                'valid_until' => now()->addYear(),
                'is_demo' => true,
            ]);

            $this->debtors[] = $org;
        }
    }

    private function seedReceivables(): void
    {
        $counter = 0;

        foreach ($this->customers as $c) {
            $contractStart = Carbon::parse($c['contract']->start_date);
            $perCustomer = mt_rand(3, 6);

            for ($k = 0; $k < $perCustomer; $k++) {
                $counter++;
                $debtor = $this->debtors[($counter * 7) % count($this->debtors)];

                $maxOffset = max(1, $contractStart->diffInDays(now()) - 5);
                $invoiceDate = $contractStart->copy()->addDays(mt_rand(1, $maxOffset));
                $dueDate = $invoiceDate->copy()->addDays(30);
                $amount = round(mt_rand(90000, 1400000) / 100, 2);

                // Aeltere Rechnungen sind ueberwiegend abgeschlossen, juengere offen
                $ageDays = $invoiceDate->diffInDays(now());
                $status = match (true) {
                    $ageDays > 120 => ['abgerechnet', 'abgerechnet', 'bezahlt', 'ausgefallen'][mt_rand(0, 3) === 3 && $counter % 17 === 0 ? 3 : mt_rand(0, 2)],
                    $ageDays > 60 => ['bezahlt', 'ausgezahlt', 'teilbezahlt', 'ueberfaellig'][mt_rand(0, 3)],
                    $ageDays > 20 => ['ausgezahlt', 'angekauft', 'freigegeben'][mt_rand(0, 2)],
                    default => ['eingereicht', 'formale_pruefung', 'freigegeben', 'angekauft'][mt_rand(0, 3)],
                };

                $receivable = Receivable::create([
                    'tenant_id' => $this->tenant->id,
                    'receivable_number' => 'FRD-TEST-'.$this->tenant->id.'-'.str_pad((string) $counter, 6, '0', STR_PAD_LEFT),
                    'organization_id' => $c['org']->id,
                    'contract_id' => $c['contract']->id,
                    'debtor_organization_id' => $debtor->id,
                    'debtor_pseudonym_id' => $debtor->pseudonym_id,
                    'invoice_number' => 'RG-'.$invoiceDate->format('ymd').'-'.$counter,
                    'invoice_date' => $invoiceDate->toDateString(),
                    'due_date' => $dueDate->toDateString(),
                    'invoice_amount' => $amount,
                    'status' => $status,
                    'source_channel' => 'kundenportal',
                    'submitted_by' => $this->staff(),
                    'reviewed_by' => in_array($status, ['eingereicht'], true) ? null : $this->staff(),
                    'is_demo' => true,
                ]);
                $receivable->forceFill(['created_at' => $invoiceDate, 'updated_at' => $invoiceDate])->saveQuietly();

                if (! in_array($status, ['eingereicht', 'formale_pruefung', 'freigegeben'], true)) {
                    $purchase = $this->calculator->calculate($receivable);
                    $first = $this->staff();
                    $purchase->update([
                        'status' => 'freigegeben',
                        'approved_by_first' => $first,
                        'approved_by_second' => $this->staff($first),
                        'purchased_at' => $invoiceDate->copy()->addDays(2),
                        'is_demo' => true, // Kalkulator setzt das Flag nicht — sonst ueberlebt der Ankauf purgeTestData()
                    ]);

                    $this->journal->post('ankauf', [
                        ['account' => '1400', 'debit' => (float) $purchase->nominal_amount, 'organization_id' => $c['org']->id, 'contract_id' => $c['contract']->id],
                        ['account' => '2100', 'credit' => (float) $purchase->immediate_payout_amount, 'organization_id' => $c['org']->id],
                        ['account' => '2000', 'credit' => (float) $purchase->reserve_amount, 'organization_id' => $c['org']->id],
                        ['account' => '4000', 'credit' => round((float) $purchase->factoring_fee_amount + (float) $purchase->expected_interest_amount, 2), 'organization_id' => $c['org']->id],
                    ], Purchase::class, $purchase->id, $purchase->approved_by_second, $invoiceDate->copy()->addDays(2), true);

                    if (in_array($status, ['ausgezahlt', 'teilbezahlt', 'bezahlt', 'abgerechnet', 'ueberfaellig'], true)) {
                        CreditLine::where('organization_id', $c['org']->id)->where('line_type', 'ankauf')->increment('used_amount', (float) $purchase->nominal_amount);
                    }
                }

                if (in_array($status, ['bezahlt', 'abgerechnet', 'teilbezahlt'], true)) {
                    $factor = $status === 'teilbezahlt' ? 0.5 : 1.0;
                    $receivable->payments()->create([
                        'tenant_id' => $this->tenant->id,
                        'amount' => round($amount * $factor, 2),
                        'type' => $status === 'teilbezahlt' ? 'teilzahlung' : 'eingang',
                        'match_confidence_percent' => 97,
                        'match_reason' => 'Testdaten: Zahlungseingang automatisch zugeordnet',
                        'matched_by' => $this->staff(),
                        'matched_at' => $dueDate->copy()->addDays(mt_rand(1, 12)),
                        'is_demo' => true,
                    ]);
                }

                if ($status === 'ueberfaellig' && $counter % 5 === 0) {
                    DunningCase::create([
                        'tenant_id' => $this->tenant->id, 'receivable_id' => $receivable->id, 'case_type' => 'mahnung',
                        'dunning_level' => 1, 'status' => 'offen', 'reason' => 'Zahlungsziel überschritten (Testdaten).',
                        'open_amount' => $amount, 'assignee_id' => $this->staff(), 'next_action_date' => now()->addDays(7), 'is_demo' => true,
                    ]);
                }
                if ($status === 'ausgefallen') {
                    DunningCase::create([
                        'tenant_id' => $this->tenant->id, 'receivable_id' => $receivable->id, 'case_type' => 'ausfall',
                        'dunning_level' => 3, 'status' => 'offen', 'reason' => 'Forderung als ausgefallen eingestuft (Testdaten).',
                        'open_amount' => $amount, 'assignee_id' => $this->staff(), 'next_action_date' => now()->addDays(10), 'is_demo' => true,
                    ]);
                }
            }
        }
    }

    private function seedInvestors(User $actor): void
    {
        $investors = [
            // [Name, Zusage, gezogen, Start, Sonderkuendigungsrecht]
            ['Müller Holding AG (Testdatensatz)', 900000, 850000, Carbon::create(2025, 2, 1), false],
            ['Enns Holding GmbH (Testdatensatz)', 1100000, 1000000, Carbon::create(2025, 4, 1), true],
            ['Deutsche Apotheker- und Ärztebank eG – apoBank (Testdatensatz)', 29500000, 14200000, Carbon::create(2025, 6, 1), false],
        ];

        foreach ($investors as $idx => [$name, $commitment, $drawn, $start, $earlyRight]) {
            $org = Organization::create([
                'tenant_id' => $this->tenant->id,
                'org_type' => 'investor',
                'name' => $name,
                'city' => ['Düsseldorf', 'Düsseldorf', 'Düsseldorf'][$idx],
                'rating' => ['AA', 'A', 'AAA'][$idx],
                'rating_points' => [84, 76, 95][$idx],
                'rating_updated_at' => now()->subMonths(2),
                'is_demo' => true,
            ]);

            $facility = Facility::create([
                'tenant_id' => $this->tenant->id,
                'investor_organization_id' => $org->id,
                'facility_number' => 'FAC-TEST-'.$this->tenant->id.'-'.($idx + 1),
                'name' => 'Refinanzierungsfazilität '.explode(' ', $name)[0],
                'commitment_amount' => $commitment,
                'drawn_amount' => $drawn,
                'interest_rate_percent' => 10.0, // Modell: ca. 1 % pro Monat, 10 % p. a., monatliche Ausschüttung
                'commitment_fee_percent' => 0.5,
                'start_date' => $start,
                'maturity_date' => $start->copy()->addYears(5),
                'seniority' => $idx === 2 ? 'senior' : 'nachrangig',
                'early_termination_right' => $earlyRight,
                'termination_notice_days' => $earlyRight ? 90 : null,
                'covenants' => ['max_leverage' => 3.5, 'min_liquidity_eur' => 250000],
                'status' => 'aktiv',
                'detail_level' => 'aggregiert',
                'is_demo' => true,
            ]);

            // Drawdown zum Start (apoBank in zwei Tranchen)
            $tranches = $idx === 2
                ? [[$start, 8000000], [$start->copy()->addMonths(4), 6200000]]
                : [[$start, $drawn]];

            foreach ($tranches as [$date, $trancheAmount]) {
                FacilityEvent::create([
                    'tenant_id' => $this->tenant->id, 'facility_id' => $facility->id, 'event_type' => 'drawdown',
                    'amount' => $trancheAmount, 'event_date' => $date, 'covenant_status' => 'eingehalten',
                    'notes' => 'Kapitalabruf (Testdaten)', 'is_demo' => true,
                ]);
                $this->journal->post('investorenziehung', [
                    ['account' => '1200', 'debit' => (float) $trancheAmount],
                    ['account' => '2500', 'credit' => (float) $trancheAmount],
                ], Facility::class, $facility->id, $this->staff(), $date, true);
            }

            // Monatliche Zinsausschuettungen von Start bis heute (nachschuessig)
            $cursor = $start->copy()->addMonth()->startOfMonth();
            while ($cursor->lte(now()->startOfMonth())) {
                $drawnAtMonth = $idx === 2 && $cursor->lt($start->copy()->addMonths(4)) ? 8000000 : $drawn;
                FacilityEvent::create([
                    'tenant_id' => $this->tenant->id,
                    'facility_id' => $facility->id,
                    'event_type' => 'zinszahlung',
                    'amount' => round($drawnAtMonth * 10.0 / 100 / 12, 2),
                    'event_date' => $cursor->copy()->addDays(2),
                    'covenant_status' => 'eingehalten',
                    'notes' => 'Monatliche Ausschüttung '.$cursor->copy()->subMonth()->format('m/Y').' (Testdaten)',
                    'is_demo' => true,
                ]);
                $cursor->addMonth();
            }
        }
    }

    private function seedCosts(User $actor): void
    {
        $cursor = Carbon::create(2025, 1, 1);
        while ($cursor->lte(now()->startOfMonth())) {
            $rows = [
                ['personal', 'Gehälter und Sozialabgaben '.$cursor->format('m/Y'), mt_rand(28000, 45000)],
                ['it', 'Softwarelizenzen, Hosting, IT-Betrieb '.$cursor->format('m/Y'), mt_rand(2800, 4200)],
                ['buero', 'Miete und Verwaltung '.$cursor->format('m/Y'), mt_rand(2200, 2900)],
                ['versicherung', 'Versicherungsprämien '.$cursor->format('m/Y'), mt_rand(1100, 1600)],
                ['refinanzierung', 'Zinsaufwand Fazilitäten '.$cursor->format('m/Y'), mt_rand(15000, 135000)],
                ['beratung', 'Recht, Steuer, Prüfung '.$cursor->format('m/Y'), mt_rand(1500, 14000)],
                ['marketing', 'Vertrieb und Empfehlungsprogramm '.$cursor->format('m/Y'), mt_rand(1200, 5500)],
            ];

            foreach ($rows as [$category, $description, $amount]) {
                OperatingCost::create([
                    'tenant_id' => $this->tenant->id,
                    'cost_date' => $cursor->copy()->addDays(mt_rand(3, 25)),
                    'category' => $category,
                    'description' => $description.' (Testdaten)',
                    'amount' => $amount,
                    'created_by' => $actor->id,
                    'is_demo' => true,
                ]);
            }

            $cursor->addMonth();
        }
    }

    private function seedCrm(): void
    {
        $leadNames = [
            'Praxis Dr. Steinbach', 'Zahnzentrum Elbufer', 'Apotheke im Bahnhofsquartier', 'Dentallabor Feinwerk GmbH',
            'Tierklinik Westend', 'MVZ Rheintal GmbH', 'Physiopraxis Auszeit', 'Pflegedienst Sonnenschein GmbH',
            'Klinik am Weinberg gGmbH', 'Laborgemeinschaft Mitte', 'Praxis Dr. Falkner', 'Zahnarztpraxis Altmark',
        ];

        foreach ($leadNames as $i => $name) {
            $lead = Lead::create([
                'tenant_id' => $this->tenant->id,
                'company_name' => $name.' (Testdaten)',
                'specialty' => array_values(RatingCatalog::SEGMENTS)[$i % count(RatingCatalog::SEGMENTS)],
                'contact_name' => 'Ansprechpartner '.($i + 1),
                'contact_email' => 'lead'.$i.'@example.com',
                'source' => $i % 2 === 0 ? 'Empfehlung' : 'Website',
                'status' => Lead::STATUSES[$i % (count(Lead::STATUSES) - 1)],
                'owner_id' => $this->staff(),
                'is_demo' => true,
            ]);

            if ($i < 6) {
                Opportunity::create([
                    'tenant_id' => $this->tenant->id,
                    'lead_id' => $lead->id,
                    'name' => 'Factoring '.$name,
                    'expected_volume' => 150000 + $i * 80000,
                    'probability_percent' => 25 + $i * 12,
                    'stage' => Opportunity::STAGES[$i % (count(Opportunity::STAGES) - 1)],
                    'expected_close_date' => now()->addDays(20 + $i * 12),
                    'next_action' => 'Angebotstermin vereinbaren',
                    'owner_id' => $this->staff(),
                    'is_demo' => true,
                ]);
            }
        }
    }

    private function seedTickets(User $actor): void
    {
        $tickets = [
            ['Frage zur Abrechnung des Sicherheitseinbehalts', 'kunde', 'beantwortet'],
            ['Auszahlungsavis nicht erhalten', 'problem', 'in_bearbeitung'],
            ['Bitte um zusätzlichen Nutzerzugang', 'wunsch', 'offen'],
            ['Frage zur monatlichen Ausschüttung', 'investor', 'beantwortet'],
            ['Export für Steuerberater', 'frage', 'geschlossen'],
        ];

        foreach ($tickets as $i => [$subject, $category, $status]) {
            Ticket::create([
                'tenant_id' => $this->tenant->id,
                'ticket_number' => 'TCK-TEST-'.$this->tenant->id.'-'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                'subject' => $subject.' (Testdaten)',
                'category' => $category,
                'status' => $status,
                'priority' => $i === 1 ? 'hoch' : 'normal',
                'created_by' => $actor->id,
                'assigned_to' => $this->staff(),
                'organization_id' => $this->customers[$i]['org']->id ?? null,
                'is_demo' => true,
            ]);
        }
    }

    /** Erzeugt unterschriebene Mustervertraege fuer 5 Kunden und alle Investoren. */
    private function seedContractDocuments(User $actor): void
    {
        foreach (array_slice($this->customers, 0, 5) as $c) {
            $document = $this->contracts->buildCustomerContract($c['contract'], $actor->id);
            $document->update([
                'visibility' => 'extern_freigegeben',
                'release_purpose' => 'vertrag',
                'released_by' => $actor->id,
                'signed_company_name' => 'Timo Müller (Testdaten)',
                'signed_company_at' => Carbon::parse($c['contract']->start_date)->addDays(1)->setTime(10, 30),
                'signed_company_by' => $actor->id,
                'signed_counterparty_name' => $c['org']->name.' (Testdaten)',
                'signed_counterparty_at' => Carbon::parse($c['contract']->start_date)->addDays(2)->setTime(9, 15),
                'signed_counterparty_by' => $actor->id,
            ]);
            $this->contracts->refresh($document->refresh(), $actor->id);
        }

        foreach (Facility::where('tenant_id', $this->tenant->id)->where('is_demo', true)->get() as $facility) {
            $document = $this->contracts->buildInvestorContract($facility, $actor->id);
            $document->update([
                'visibility' => 'extern_freigegeben',
                'release_purpose' => 'vertrag',
                'release_audience' => 'investor',
                'released_by' => $actor->id,
                'signed_company_name' => 'Timo Müller (Testdaten)',
                'signed_company_at' => Carbon::parse($facility->start_date)->subDays(3)->setTime(11, 0),
                'signed_company_by' => $actor->id,
                'signed_counterparty_name' => $facility->investorOrganization->name,
                'signed_counterparty_at' => Carbon::parse($facility->start_date)->subDays(2)->setTime(16, 45),
                'signed_counterparty_by' => $actor->id,
            ]);
            $this->contracts->refresh($document->refresh(), $actor->id);
        }
    }
}
