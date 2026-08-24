<?php

namespace App\Services;

use App\Models\CreditLine;
use App\Models\DunningCase;
use App\Models\Facility;
use App\Models\FacilityEvent;
use App\Models\Payout;
use App\Models\Purchase;
use App\Models\Receivable;
use Illuminate\Support\Carbon;

/**
 * Zentrale, nachvollziehbare KPI-Berechnungen gemaess Abschnitt 15.5 des Masterprompts.
 * Jede Methode liefert einen Wert, der direkt aus den Bewegungsdaten reproduzierbar ist.
 */
class KpiService
{
    public function customerAvailableToday(int $organizationId): float
    {
        $line = CreditLine::where('organization_id', $organizationId)
            ->where('line_type', 'auszahlung')
            ->where('status', 'aktiv')
            ->first();

        if (! $line) {
            return 0.0;
        }

        return max(0.0, (float) $line->limit_amount - (float) $line->used_amount);
    }

    public function customerPayoutSum(int $organizationId, ?Carbon $from = null, ?Carbon $to = null): float
    {
        $query = Payout::whereHas('purchase.receivable', fn ($q) => $q->where('organization_id', $organizationId))
            ->where('status', 'bestaetigt');

        if ($from) {
            $query->where('confirmed_at', '>=', $from);
        }
        if ($to) {
            $query->where('confirmed_at', '<=', $to);
        }

        return (float) $query->sum('amount');
    }

    public function customerReceivablesInReview(int $organizationId): array
    {
        $q = Receivable::where('organization_id', $organizationId)
            ->whereIn('status', ['eingereicht', 'formale_pruefung', 'risiko_limitpruefung', 'rueckfrage']);

        return ['count' => $q->count(), 'amount' => (float) $q->sum('invoice_amount')];
    }

    public function customerActionRequired(int $organizationId): int
    {
        return Receivable::where('organization_id', $organizationId)
            ->whereIn('status', ['rueckfrage', 'abgelehnt'])
            ->count();
    }

    public function customerCosts(int $organizationId): array
    {
        $purchases = Purchase::whereHas('receivable', fn ($q) => $q->where('organization_id', $organizationId));

        return [
            'fees' => (float) $purchases->clone()->sum('factoring_fee_amount'),
            'interest' => (float) $purchases->clone()->sum('expected_interest_amount'),
        ];
    }

    public function creditLineUtilizationPercent(CreditLine $line): float
    {
        if ((float) $line->limit_amount <= 0) {
            return 0.0;
        }

        return round(((float) $line->used_amount / (float) $line->limit_amount) * 100, 2);
    }

    public function facilityUtilizationPercent(Facility $facility): float
    {
        if ((float) $facility->commitment_amount <= 0) {
            return 0.0;
        }

        return round(((float) $facility->drawn_amount / (float) $facility->commitment_amount) * 100, 2);
    }

    public function freeLiquidity(float $bankBalances, float $securedLines, float $reservedPayouts, float $minLiquidity): float
    {
        return $bankBalances + $securedLines - $reservedPayouts - $minLiquidity;
    }

    public function grossRevenue(?Carbon $from = null, ?Carbon $to = null): float
    {
        $q = Purchase::query();
        if ($from) {
            $q->where('purchased_at', '>=', $from);
        }
        if ($to) {
            $q->where('purchased_at', '<=', $to);
        }

        return (float) $q->sum('factoring_fee_amount') + (float) $q->clone()->sum('expected_interest_amount');
    }

    public function refinancingCost(): float
    {
        return (float) FacilityEvent::where('event_type', 'zinszahlung')->sum('amount');
    }

    public function realizedLosses(): float
    {
        return (float) DunningCase::where('case_type', 'ausfall')->sum('open_amount');
    }

    public function contributionMargin(): float
    {
        return $this->grossRevenue() - $this->refinancingCost() - $this->realizedLosses();
    }

    public function dilutionRatePercent(): float
    {
        $nominal = (float) Purchase::sum('nominal_amount');
        if ($nominal <= 0) {
            return 0.0;
        }

        return round(((float) Purchase::sum('deductions_amount') / $nominal) * 100, 2);
    }

    public function overdueRatioPercent(?float $totalOpen = null): float
    {
        $openStatuses = ['angekauft', 'zur_auszahlung', 'zahlung_angewiesen', 'ausgezahlt', 'teilbezahlt', 'ueberfaellig'];
        // Aufrufer, die die offene Gesamtsumme bereits berechnet haben (z.B. das
        // GL-Dashboard), koennen sie uebergeben und sparen die doppelte Full-Table-Summe.
        $totalOpen ??= (float) Receivable::whereIn('status', $openStatuses)->sum('invoice_amount');
        if ($totalOpen <= 0) {
            return 0.0;
        }
        $overdue = (float) Receivable::where('status', 'ueberfaellig')->sum('invoice_amount');

        return round(($overdue / $totalOpen) * 100, 2);
    }

    public function top10ConcentrationPercent(): float
    {
        $total = (float) Receivable::sum('invoice_amount');
        if ($total <= 0) {
            return 0.0;
        }

        $top10 = Receivable::selectRaw('debtor_organization_id, SUM(invoice_amount) as total')
            ->whereNotNull('debtor_organization_id')
            ->groupBy('debtor_organization_id')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->sum('total');

        return round(($top10 / $total) * 100, 2);
    }

    public function averageDso(): float
    {
        // Aggregation in SQL (eine Zeile je Forderung mit letztem Zahldatum) statt
        // alle jemals bezahlten Forderungen samt Zahlungen als Modelle zu laden.
        $rows = Receivable::query()
            ->whereIn('receivables.status', ['bezahlt', 'abgerechnet'])
            ->join('payments', 'payments.receivable_id', '=', 'receivables.id')
            ->groupBy('receivables.id', 'receivables.invoice_date')
            ->selectRaw('receivables.invoice_date as invoice_date, MAX(payments.matched_at) as last_payment')
            ->get();

        $days = $rows->map(fn ($r) => $r->last_payment
            ? Carbon::parse($r->invoice_date)->diffInDays(Carbon::parse($r->last_payment))
            : null)->filter();

        return $days->isEmpty() ? 0.0 : round($days->avg(), 1);
    }

    public function ageingBuckets(): array
    {
        $openStatuses = ['angekauft', 'zur_auszahlung', 'zahlung_angewiesen', 'ausgezahlt', 'teilbezahlt', 'ueberfaellig'];

        // Eine Aggregatabfrage statt Hydrierung aller offenen Forderungen.
        // Faelligkeit <= 30 Tage ueberfaellig (inkl. noch nicht faellig) = Bucket 0-30.
        $d30 = now()->subDays(30)->toDateString();
        $d60 = now()->subDays(60)->toDateString();
        $d90 = now()->subDays(90)->toDateString();

        $row = Receivable::whereIn('status', $openStatuses)->selectRaw('
            COALESCE(SUM(CASE WHEN due_date >= ? THEN invoice_amount ELSE 0 END), 0) as b1,
            COALESCE(SUM(CASE WHEN due_date < ? AND due_date >= ? THEN invoice_amount ELSE 0 END), 0) as b2,
            COALESCE(SUM(CASE WHEN due_date < ? AND due_date >= ? THEN invoice_amount ELSE 0 END), 0) as b3,
            COALESCE(SUM(CASE WHEN due_date < ? THEN invoice_amount ELSE 0 END), 0) as b4
        ', [$d30, $d30, $d60, $d60, $d90, $d90])->first();

        return [
            '0-30' => (float) $row->b1,
            '31-60' => (float) $row->b2,
            '61-90' => (float) $row->b3,
            '>90' => (float) $row->b4,
        ];
    }
}
